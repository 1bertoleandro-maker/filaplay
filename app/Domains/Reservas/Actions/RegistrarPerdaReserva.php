<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Actions;

use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Models\User;
use App\Domains\Reservas\Enums\ReservaStatus;
use App\Domains\Reservas\Models\Reserva;
use App\Domains\Reservas\Services\RegistroAtividade;
use App\Domains\Reservas\Services\ReorganizaFilaDaQuadra;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Recepção ou administrador marca a reserva como perdida e puxa
 * para frente quem ainda está na fila da mesma quadra.
 */
final class RegistrarPerdaReserva
{
    /** @var array<string, string> */
    public const MOTIVOS = [
        'nao_apareceu' => 'Não apareceu',
        'machucou' => 'Machucou',
        'desistiu' => 'Desistiu',
    ];

    public function __construct(
        private readonly ReorganizaFilaDaQuadra $fila,
        private readonly RegistroAtividade $atividades,
    ) {}

    public function handle(Reserva $reserva, string $motivo, User $actor): void
    {
        abort_unless(in_array($actor->role, [UserRole::Administrador, UserRole::Recepcao], true), 403);

        if (! isset(self::MOTIVOS[$motivo])) {
            throw ValidationException::withMessages(['motivo' => 'Escolha o motivo da perda.']);
        }

        if ($reserva->status !== ReservaStatus::Confirmada) {
            throw ValidationException::withMessages(['reserva' => 'Essa reserva já não está na fila.']);
        }

        DB::transaction(function () use ($reserva, $motivo): void {
            $reserva->update([
                'status' => ReservaStatus::NoShow,
                'observacoes' => trim((string) $reserva->observacoes.' Perda: '.self::MOTIVOS[$motivo].'.'),
            ]);

            $this->fila->antecipar($reserva);
        });

        $reserva->loadMissing(['quadra', 'user']);
        $this->atividades->registrar(
            'reserva_perdida',
            'Reserva perdida · '.($reserva->quadra?->apelido ?: $reserva->quadra?->nome),
            self::MOTIVOS[$motivo].' · '.$reserva->inicio->format('H:i').'. A fila andou.',
            $reserva->quadra_id,
            tenantId: $reserva->tenant_id,
        );
    }
}
