<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

final class TenantContext
{
    private ?int $tenantId = null;

    public function set(?int $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function id(): ?int
    {
        return $this->tenantId;
    }

    public function forget(): void
    {
        $this->tenantId = null;
    }
}
