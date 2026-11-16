<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Services;

use App\Domains\Reservas\Models\Atividade;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Grava e lê a linha do tempo do clube. Nunca derruba a operação principal.
 */
final class RegistroAtividade
{
    public function registrar(
        string $tipo,
        string $titulo,
        ?string $detalhe = null,
        ?int $quadraId = null,
        ?int $userId = null,
        ?int $tenantId = null,
    ): void {
        $tenantId ??= app(TenantContext::class)->id();

        if ($tenantId === null) {
            return;
        }

        try {
            Atividade::query()->create([
                'tenant_id' => $tenantId,
                'tipo' => $tipo,
                'titulo' => mb_substr($titulo, 0, 160),
                'detalhe' => $detalhe !== null ? mb_substr($detalhe, 0, 255) : null,
                'quadra_id' => $quadraId,
                'user_id' => $userId,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * @return Collection<int, Atividade>
     */
    public function recentes(int $limite = 20): Collection
    {
        return Atividade::query()
            ->where('created_at', '>=', now()->subHours(12))
            ->orderByDesc('id')
            ->limit($limite)
            ->get();
    }
}
