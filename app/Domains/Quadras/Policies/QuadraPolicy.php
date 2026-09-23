<?php

declare(strict_types=1);

namespace App\Domains\Quadras\Policies;

use App\Domains\Jogadores\Models\User;
use App\Domains\Quadras\Models\Quadra;
use App\Support\Authorization\TenantPolicy;

class QuadraPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Quadra $quadra): bool
    {
        return $this->sameTenant($user, $quadra);
    }

    public function create(User $user): bool
    {
        return $this->managesClub($user);
    }

    public function update(User $user, Quadra $quadra): bool
    {
        return $this->managesClub($user) && $this->sameTenant($user, $quadra);
    }

    public function delete(User $user, Quadra $quadra): bool
    {
        return $this->update($user, $quadra);
    }
}
