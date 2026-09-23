<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Actions;

use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Models\User;
use App\Domains\Reservas\Enums\ReservaStatus;
use App\Domains\Reservas\Models\Reserva;

final class CancelarReserva
{
    public function handle(User $actor, Reserva $reserva): Reserva
    {
        abort_unless(in_array($actor->role, [UserRole::Administrador, UserRole::Recepcao], true), 403);
        abort_unless((int) $actor->tenant_id === (int) $reserva->tenant_id, 403);

        $reserva->update(['status' => ReservaStatus::Cancelada]);

        return $reserva->fresh();
    }
}
