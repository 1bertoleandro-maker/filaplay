<?php

declare(strict_types=1);

namespace App\Domains\Presenca\Policies;

use App\Domains\Jogadores\Models\User;
use App\Domains\Presenca\Models\Presenca;
use App\Support\Authorization\TenantPolicy;

class PresencaPolicy extends TenantPolicy
{
    public function view(User $user, Presenca $presenca): bool
    {
        if (! $this->sameTenant($user, $presenca)) {
            return false;
        }

        return $user->id === $presenca->user_id || $this->managesPeople($user);
    }

    public function create(User $user): bool
    {
        return $user->podeAcessar();
    }
}
