<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Services;

use App\Domains\Clube\Services\HorarioDoClube;
use App\Domains\Quadras\Models\Bloqueio;
use App\Domains\Reservas\Models\Reserva;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mantém a fila de reservas da quadra “encadeada” sem buracos nem sobreposição:
 * - antecipar: alguém saiu/perdeu, quem vem depois puxa para frente;
 * - estender: a reserva atual ganhou minutos, quem vem depois é empurrado.
 * Bloqueios (aula, torneio, manutenção) nunca são atropelados.
 */
final class ReorganizaFilaDaQuadra
{
    public function __construct(
        private readonly GradeAgenda $grade,
        private readonly HorarioDoClube $horario,
    ) {}

    /**
     * Puxa para frente as reservas posteriores a $referencia (que saiu da fila).
     */
    public function antecipar(Reserva $referencia): void
    {
        $agora = $this->grade->arredondarParaCinco(now());
        $cursor = $referencia->inicio->gt($agora) ? $referencia->inicio->copy() : $agora;
        $bloqueios = $this->bloqueios($referencia);

        foreach ($this->seguintes($referencia) as $proxima) {
            $duracao = $this->duracaoEmMinutos($proxima);
            $candidato = $this->desviarDeBloqueios($cursor, $duracao, $bloqueios);

            if ($candidato->gte($proxima->inicio)) {
                // Nada a ganhar: mantém o horário e continua a corrente a partir dele.
                $cursor = $proxima->fim->copy();

                continue;
            }

            $novoFim = $candidato->copy()->addMinutes($duracao);
            $proxima->update(['inicio' => $candidato, 'fim' => $novoFim]);
            $cursor = $novoFim;
        }
    }

    /**
     * Soma minutos ao fim de $reserva e empurra as seguintes se for preciso.
     */
    public function estender(Reserva $reserva, int $minutos): void
    {
        if ($minutos < 1 || $minutos > 60) {
            throw ValidationException::withMessages(['minutos' => 'Escolha entre 1 e 60 minutos.']);
        }

        $tenant = $reserva->quadra->tenant;
        $novoFim = $reserva->fim->copy()->addMinutes($minutos);

        if (! $this->horario->cabe($tenant, $reserva->inicio, $novoFim)) {
            throw ValidationException::withMessages(['minutos' => 'Passa do horário de funcionamento do clube.']);
        }

        $bloqueios = $this->bloqueios($reserva);

        $baterNoBloqueio = $bloqueios->first(
            fn (Bloqueio $b): bool => $b->inicio->lt($novoFim) && $b->fim->gt($reserva->fim)
        );

        if ($baterNoBloqueio !== null) {
            throw ValidationException::withMessages([
                'minutos' => 'A quadra está bloqueada a partir das '.$baterNoBloqueio->inicio->format('H:i').' ('.$baterNoBloqueio->motivo.').',
            ]);
        }

        $plano = [];
        $cursor = $novoFim->copy();

        foreach ($this->seguintes($reserva) as $proxima) {
            $duracao = $this->duracaoEmMinutos($proxima);
            $base = $proxima->inicio->gt($cursor) ? $proxima->inicio->copy() : $cursor->copy();
            $inicio = $this->desviarDeBloqueios($base, $duracao, $bloqueios);
            $fim = $inicio->copy()->addMinutes($duracao);

            if (! $this->horario->cabe($tenant, $inicio, $fim)) {
                throw ValidationException::withMessages([
                    'minutos' => 'Com esse acréscimo a próxima reserva passaria do horário do clube.',
                ]);
            }

            if ($inicio->ne($proxima->inicio)) {
                $plano[] = [$proxima, $inicio, $fim];
            }

            $cursor = $fim;
        }

        DB::transaction(function () use ($reserva, $novoFim, $plano): void {
            $reserva->update(['fim' => $novoFim]);

            foreach ($plano as [$proxima, $inicio, $fim]) {
                $proxima->update(['inicio' => $inicio, 'fim' => $fim]);
            }
        });
    }

    /**
     * @return Collection<int, Reserva>
     */
    private function seguintes(Reserva $referencia): Collection
    {
        return Reserva::query()
            ->confirmadas()
            ->where('quadra_id', $referencia->quadra_id)
            ->where('id', '!=', $referencia->id)
            ->where('inicio', '>=', $referencia->inicio)
            ->orderBy('inicio')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @return Collection<int, Bloqueio>
     */
    private function bloqueios(Reserva $referencia): Collection
    {
        return Bloqueio::query()
            ->where('quadra_id', $referencia->quadra_id)
            ->where('fim', '>', now())
            ->orderBy('inicio')
            ->get();
    }

    /**
     * @param  Collection<int, Bloqueio>  $bloqueios
     */
    private function desviarDeBloqueios(Carbon $inicio, int $duracao, Collection $bloqueios): Carbon
    {
        $candidato = $inicio->copy();

        foreach ($bloqueios as $bloqueio) {
            if ($candidato->lt($bloqueio->fim) && $candidato->copy()->addMinutes($duracao)->gt($bloqueio->inicio)) {
                $candidato = $bloqueio->fim->copy();
            }
        }

        return $candidato;
    }

    private function duracaoEmMinutos(Reserva $reserva): int
    {
        return max(1, (int) round(($reserva->fim->getTimestamp() - $reserva->inicio->getTimestamp()) / 60));
    }
}
