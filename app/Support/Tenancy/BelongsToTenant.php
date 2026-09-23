<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Domains\Clube\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('tenant_id')) {
                return;
            }

            $tenantId = app(TenantContext::class)->id();

            if ($tenantId !== null) {
                $model->setAttribute('tenant_id', $tenantId);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
