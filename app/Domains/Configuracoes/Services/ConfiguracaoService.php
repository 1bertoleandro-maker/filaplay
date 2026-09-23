<?php

declare(strict_types=1);

namespace App\Domains\Configuracoes\Services;

use App\Domains\Configuracoes\Models\Configuracao;
use App\Support\Tenancy\TenantContext;

final class ConfiguracaoService
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $chave): ?array
    {
        $tenantId = $this->tenantContext->id();

        $query = Configuracao::query()->where('chave', $chave);

        if ($tenantId === null) {
            return null;
        }

        return $query->first()?->valor;
    }
}
