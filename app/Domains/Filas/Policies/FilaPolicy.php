<?php

declare(strict_types=1);

namespace App\Domains\Filas\Policies;

use App\Domains\Filas\Models\Fila;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Models\User;
use App\Support\Authorization\TenantPolicy;

class FilaPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Fila $fila): bool
    {
        return $this->sameTenant($user, $fila);
    }

    public function create(User $user): bool
    {
        return $user->podeAcessar();
    }

    public function update(User $user, Fila $fila): bool
    {
        if (! $this->sameTenant($user, $fila)) {
            return false;
        }

        return in_array($user->role, [UserRole::Administrador, UserRole::Professor, UserRole::Recepcao], true)
            || $user->id === $fila->user_id;
    }
}
