<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Policies;

use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Models\User;
use App\Domains\Reservas\Models\Reserva;
use App\Support\Authorization\TenantPolicy;

class ReservaPolicy extends TenantPolicy
{
    public function view(User $user, Reserva $reserva): bool
    {
        if (! $this->sameTenant($user, $reserva)) {
            return false;
        }

        return $user->id === $reserva->user_id
            || in_array($user->role, [UserRole::Administrador, UserRole::Recepcao, UserRole::Professor], true);
    }

    public function create(User $user): bool
    {
        return $user->role !== UserRole::Jogador || $user->podeAcessar();
    }
}
