<?php

declare(strict_types=1);

namespace App\Domains\Partidas\Policies;

use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Models\User;
use App\Domains\Partidas\Models\Partida;
use App\Support\Authorization\TenantPolicy;

class PartidaPolicy extends TenantPolicy
{
    public function view(User $user, Partida $partida): bool
    {
        return $this->sameTenant($user, $partida);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Administrador, UserRole::Professor], true);
    }

    public function update(User $user, Partida $partida): bool
    {
        return $this->create($user) && $this->sameTenant($user, $partida);
    }
}
