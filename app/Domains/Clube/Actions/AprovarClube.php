<?php

declare(strict_types=1);

namespace App\Domains\Clube\Actions;

use App\Domains\Clube\Enums\TenantStatus;
use App\Domains\Clube\Models\Tenant;
use App\Domains\Jogadores\Models\User;
use Illuminate\Support\Facades\Log;

final class AprovarClube
{
    public function aprovar(User $superAdmin, Tenant $tenant): Tenant
    {
        abort_unless($superAdmin->super_admin, 403);

        $tenant->update(['status' => TenantStatus::Ativo, 'aprovado_em' => now()]);

        // E-mail real de liberação é enviado assim que o mailer do subdomínio
        // gotreino.com estiver configurado; por ora fica registrado no log.
        Log::info("Clube aprovado: {$tenant->nome} ({$tenant->email})");

        return $tenant->fresh();
    }

    public function bloquear(User $superAdmin, Tenant $tenant): Tenant
    {
        abort_unless($superAdmin->super_admin, 403);

        $tenant->update(['status' => TenantStatus::Bloqueado]);

        return $tenant->fresh();
    }
}
