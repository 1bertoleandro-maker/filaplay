<?php

declare(strict_types=1);

namespace App\Domains\Clube\Policies;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Models\User;

class TenantPolicy
{
    public function view(User $user, Tenant $tenant): bool
    {
        return (int) $user->tenant_id === (int) $tenant->id;
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return in_array($user->role, [UserRole::Administrador, UserRole::Recepcao], true)
            && (int) $user->tenant_id === (int) $tenant->id;
    }
}
