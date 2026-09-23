<?php

declare(strict_types=1);

namespace App\Support\Authorization;

use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class TenantPolicy
{
    protected function sameTenant(User $user, Model $model): bool
    {
        return (int) $user->tenant_id === (int) $model->getAttribute('tenant_id');
    }

    protected function isAdmin(User $user): bool
    {
        return $user->role === UserRole::Administrador;
    }

    protected function managesClub(User $user): bool
    {
        return in_array($user->role, [UserRole::Administrador, UserRole::Recepcao], true);
    }

    protected function managesPeople(User $user): bool
    {
        return $this->managesClub($user);
    }
}
