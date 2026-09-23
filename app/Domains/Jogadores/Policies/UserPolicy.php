<?php

declare(strict_types=1);

namespace App\Domains\Jogadores\Policies;

use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Models\User;
use App\Support\Authorization\TenantPolicy;

class UserPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->managesPeople($user) || $user->role === UserRole::Professor;
    }

    public function view(User $user, User $model): bool
    {
        if (! $this->sameTenant($user, $model)) {
            return false;
        }

        return $user->id === $model->id || $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->managesPeople($user);
    }

    public function update(User $user, User $model): bool
    {
        if (! $this->sameTenant($user, $model)) {
            return false;
        }

        return $user->id === $model->id || $this->managesPeople($user);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->isAdmin($user)
            && $this->sameTenant($user, $model)
            && $user->id !== $model->id;
    }
}
