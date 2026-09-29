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
        $tenantId = $this->tenantContext->id() ?? auth()->user()?->tenant_id;

        if ($tenantId === null) {
            return null;
        }

        return Configuracao::query()
            ->where('tenant_id', $tenantId)
            ->where('chave', $chave)
            ->first()?->valor;
    }

    /**
     * @param  array<string, mixed>  $valor
     */
    public function set(string $chave, array $valor): void
    {
        $tenantId = $this->tenantContext->id() ?? auth()->user()?->tenant_id;

        if ($tenantId === null) {
            return;
        }

        Configuracao::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'chave' => $chave],
            ['valor' => $valor],
        );
    }

    public function ativo(string $chave, bool $padrao = false): bool
    {
        $valor = $this->get($chave);

        if ($valor === null) {
            return $padrao;
        }

        return (bool) ($valor['ativo'] ?? $padrao);
    }
}
