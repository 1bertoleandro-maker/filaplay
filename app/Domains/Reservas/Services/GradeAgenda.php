<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Services;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Clube\Services\HorarioDoClube;
use App\Domains\Configuracoes\ConfiguracaoChave;
use App\Domains\Configuracoes\Services\ConfiguracaoService;
use App\Domains\Jogadores\Models\User;
use App\Domains\Quadras\Models\Bloqueio;
use App\Domains\Quadras\Models\Quadra;
use App\Domains\Reservas\Models\Reserva;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Agenda contínua do clube.
 *
 * Regra de ouro: se a quadra tem reserva ativa/futura, o próximo início
 * é o fim da última; se está livre, é “agora” arredondado em 5 minutos.
 */
final class GradeAgenda
{
    public function __construct(
        private readonly HorarioDoClube $horario,
        private readonly ConfiguracaoService $config,
    ) {}

    /** 10:04 → 10:05; 10:05 → 10:05. */
    public function arredondarParaCinco(CarbonInterface $momento): Carbon
    {
        $copia = Carbon::parse($momento)->seconds(0)->microseconds(0);
        $resto = ((int) $copia->minute) % 5;

        return $resto === 0 ? $copia : $copia->addMinutes(5 - $resto);
    }

    public function duracaoPadraoMinutos(): int
    {
        return (int) ($this->config->get(ConfiguracaoChave::MODALIDADE_SIMPLES_MINUTOS)['minutos'] ?? 60);
    }

    /**
     * Sugere o início da próxima reserva na quadra (regra de ouro).
     */
    public function proximoInicioDisponivel(
        Quadra $quadra,
        Tenant $tenant,
        ?int $duracaoMinutos = null,
        ?CarbonInterface $referencia = null,
    ): ?Carbon {
        $agora = Carbon::parse($referencia ?? now());
        $duracao = $duracaoMinutos ?? $this->duracaoPadraoMinutos();
        $candidato = $this->arredondarParaCinco($agora);

        $ocupacoes = $quadra->relationLoaded('reservas')
            ? $quadra->reservas->where('fim', '>', $agora)->sortBy('fim')->values()
            : Reserva::query()
                ->confirmadas()
                ->where('quadra_id', $quadra->id)
                ->where('fim', '>', $agora)
                ->orderBy('fim')
                ->get(['inicio', 'fim']);

        foreach ($ocupacoes as $ocupacao) {
            if ($candidato->lt($ocupacao->fim) && $candidato->copy()->addMinutes($duracao)->gt($ocupacao->inicio)) {
                $candidato = Carbon::parse($ocupacao->fim);
            }
        }

        $bloqueios = $quadra->relationLoaded('bloqueios')
            ? $quadra->bloqueios->where('fim', '>', $agora)->sortBy('fim')->values()
            : Bloqueio::query()
                ->where('quadra_id', $quadra->id)
                ->where('fim', '>', $agora)
                ->orderBy('fim')
                ->get(['inicio', 'fim']);

        foreach ($bloqueios as $bloqueio) {
            if ($candidato->lt($bloqueio->fim) && $candidato->copy()->addMinutes($duracao)->gt($bloqueio->inicio)) {
                $candidato = Carbon::parse($bloqueio->fim);
            }
        }

        $fim = $candidato->copy()->addMinutes($duracao);

        return $this->horario->cabe($tenant, $candidato, $fim) ? $candidato : null;
    }

    /**
     * @return array{dia: Carbon, colunas: Collection<int, array<string, mixed>>, duracao_minutos: int}
     */
    public function montar(Tenant $tenant, ?CarbonInterface $dia = null): array
    {
        $dia = Carbon::parse(($dia ?? now())->toDateString())->startOfDay();
        $agora = now();
        $duracao = $this->duracaoPadraoMinutos();
        $limiteAgenda = $agora->copy()->subHours(2);

        // Eager load moderno: só o que a TV/tablet precisa ver.
        $quadras = Quadra::query()
            ->orderBy('ordem_exibicao')
            ->with([
                'reservas' => fn ($query) => $query
                    ->with(['user', 'jogadores'])
                    ->naAgenda($limiteAgenda),
                'bloqueios' => fn ($query) => $query
                    ->where('fim', '>', $dia->copy()->startOfDay())
                    ->where('inicio', '<', $dia->copy()->endOfDay())
                    ->orderBy('inicio'),
            ])
            ->get();

        $colunas = $quadras->map(function (Quadra $quadra) use ($agora, $duracao, $tenant): array {
            $doDia = $quadra->reservas->sortBy('inicio')->values();

            $atual = $doDia->first(
                fn (Reserva $item): bool => $item->inicio->lte($agora) && $item->fim->gt($agora)
            );
            $proxima = $doDia->first(fn (Reserva $item): bool => $item->inicio->gt($agora));

            $bloqueadaAgora = $quadra->bloqueios->contains(
                fn (Bloqueio $bloqueio): bool => $bloqueio->inicio->lte($agora) && $bloqueio->fim->gt($agora)
            );

            $status = match (true) {
                $bloqueadaAgora => 'bloqueada',
                $atual !== null => 'em_jogo',
                $proxima !== null && $proxima->inicio->diffInMinutes($agora) <= 60 => 'proximo',
                default => 'livre',
            };

            $proximoLivre = $this->proximoInicioDisponivel($quadra, $tenant, $duracao, $agora);

            $timeline = $doDia
                ->filter(fn (Reserva $item): bool => $item->fim->gt($agora->copy()->subMinutes(30)))
                ->map(fn (Reserva $reserva): array => [
                    'tipo' => 'reserva',
                    'inicio' => $reserva->inicio->format('H:i'),
                    'fim' => $reserva->fim->format('H:i'),
                    'atual' => $reserva->inicio->lte($agora) && $reserva->fim->gt($agora),
                    'passado' => $reserva->fim->lte($agora),
                    'reserva' => $reserva,
                    'jogadores' => $this->cartoes($reserva->elenco()),
                ])
                ->values()
                ->all();

            return [
                'quadra' => $quadra,
                'status' => $status,
                'atual' => $atual,
                'proxima' => $proxima,
                'jogadores_atual' => $atual ? $this->cartoes($atual->elenco()) : [],
                'jogadores_proxima' => $proxima ? $this->cartoes($proxima->elenco()) : [],
                'restante' => $atual ? max(0, $atual->fim->getTimestamp() - $agora->getTimestamp()) : null,
                'duracao' => $atual ? max(1, $atual->fim->getTimestamp() - $atual->inicio->getTimestamp()) : null,
                'proximo_livre' => $proximoLivre?->format('H:i'),
                'proximo_livre_fim' => $proximoLivre?->copy()->addMinutes($duracao)->format('H:i'),
                'pode_reservar_agora' => $proximoLivre !== null && ! $bloqueadaAgora,
                'timeline' => $timeline,
            ];
        });

        return [
            'dia' => $dia,
            'colunas' => $colunas,
            'duracao_minutos' => $duracao,
        ];
    }

    /**
     * @param  Collection<int, User>  $jogadores
     * @return array<int, array{id:int, nome:string, primeiro_nome:string, iniciais:string, foto:?string, matricula:string, tem_facial:bool}>
     */
    public function cartoes(Collection $jogadores): array
    {
        return $jogadores->map(fn (User $jogador): array => $this->cartao($jogador))->all();
    }

    /**
     * @return array{id:int, nome:string, primeiro_nome:string, iniciais:string, foto:?string, face:?string, matricula:string, tem_facial:bool}
     */
    public function cartao(User $jogador): array
    {
        return [
            'id' => $jogador->id,
            'nome' => $jogador->nome,
            'primeiro_nome' => $jogador->primeiroNome(),
            'iniciais' => $jogador->iniciais(),
            'foto' => $jogador->avatarUrl,
            'face' => $jogador->faceUrl,
            'matricula' => (string) $jogador->matricula,
            'tem_facial' => (bool) $jogador->cadastro_facial_completo,
        ];
    }
}
