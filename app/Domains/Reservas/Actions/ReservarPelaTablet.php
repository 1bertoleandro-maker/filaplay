<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Actions;

use App\Domains\Clube\Services\HorarioDoClube;
use App\Domains\Jogadores\Models\User;
use App\Domains\Presenca\Enums\PresencaMetodo;
use App\Domains\Presenca\Models\Presenca;
use App\Domains\Quadras\Models\Bloqueio;
use App\Domains\Quadras\Models\Quadra;
use App\Domains\Reservas\Enums\ReservaOrigem;
use App\Domains\Reservas\Enums\ReservaStatus;
use App\Domains\Reservas\Models\Reserva;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reserva feita pelo próprio sócio na tela fixa do tablet: escolhe a quadra,
 * digita o código dele e o código do colega e salva. Sem secretária, sem
 * reconhecimento facial — a identificação é pelo código (matrícula) de cada um.
 */
final class ReservarPelaTablet
{
    public function __construct(private readonly HorarioDoClube $horario) {}

    public function handle(
        Quadra $quadra,
        string $matriculaPrincipal,
        ?string $matriculaParceiro,
        string $data,
        string $hora,
        int $duracaoMinutos = 60,
    ): Reserva {
        $tenant = $quadra->tenant;

        $principal = $this->buscarSocio($tenant->id, $matriculaPrincipal, 'matricula');
        $parceiro = null;

        if ($matriculaParceiro !== null && $matriculaParceiro !== '') {
            $parceiro = $this->buscarSocio($tenant->id, $matriculaParceiro, 'matricula_parceiro');

            if ($parceiro->id === $principal->id) {
                throw ValidationException::withMessages([
                    'matricula_parceiro' => 'Digite o código de outra pessoa.',
                ]);
            }
        }

        $inicio = Carbon::parse($data.' '.$hora);
        $fim = $inicio->copy()->addMinutes($duracaoMinutos);

        if ($fim->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages(['hora' => 'Esse horário já passou.']);
        }

        if (! $this->horario->cabe($tenant, $inicio, $fim)) {
            throw ValidationException::withMessages(['hora' => 'Fora do horário de funcionamento do clube.']);
        }

        $conflito = Reserva::query()
            ->where('quadra_id', $quadra->id)
            ->where('status', ReservaStatus::Confirmada)
            ->where('inicio', '<', $fim)
            ->where('fim', '>', $inicio)
            ->exists();

        $bloqueio = Bloqueio::query()
            ->where('quadra_id', $quadra->id)
            ->where('inicio', '<', $fim)
            ->where('fim', '>', $inicio)
            ->exists();

        if ($conflito || $bloqueio) {
            throw ValidationException::withMessages(['hora' => 'Este horário já está ocupado.']);
        }

        $observacao = 'Reservado no tablet por '.$principal->nome.($parceiro ? ' e '.$parceiro->nome : '');

        return DB::transaction(function () use ($quadra, $tenant, $principal, $parceiro, $inicio, $fim, $observacao): Reserva {
            $reserva = Reserva::query()->create([
                'tenant_id' => $tenant->id,
                'quadra_id' => $quadra->id,
                'user_id' => $principal->id,
                'inicio' => $inicio,
                'fim' => $fim,
                'origem' => ReservaOrigem::App,
                'status' => ReservaStatus::Confirmada,
                'observacoes' => $observacao,
            ]);

            foreach (array_filter([$principal, $parceiro]) as $pessoa) {
                Presenca::query()->create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $pessoa->id,
                    'metodo' => PresencaMetodo::Tablet,
                    'validado_em' => now(),
                ]);
            }

            return $reserva;
        });
    }

    private function buscarSocio(int $tenantId, string $matricula, string $campo): User
    {
        $socio = User::query()
            ->where('tenant_id', $tenantId)
            ->where('matricula', $matricula)
            ->first();

        if ($socio === null) {
            throw ValidationException::withMessages([$campo => 'Código não encontrado.']);
        }

        if ($socio->bloqueado) {
            throw ValidationException::withMessages([$campo => 'Este sócio está bloqueado.']);
        }

        return $socio;
    }
}
