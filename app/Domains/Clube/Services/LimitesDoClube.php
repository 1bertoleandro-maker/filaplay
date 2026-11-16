<?php

declare(strict_types=1);

namespace App\Domains\Clube\Services;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Models\User;
use App\Support\Tenancy\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * Limites do plano de cada clube, definidos pelo master:
 * equipe (administrador, recepção, professor) e sócios (jogadores).
 */
final class LimitesDoClube
{
    /** @var array<int, UserRole> */
    private const PAPEIS_DE_EQUIPE = [UserRole::Administrador, UserRole::Recepcao, UserRole::Professor];

    public function ehEquipe(UserRole $papel): bool
    {
        return in_array($papel, self::PAPEIS_DE_EQUIPE, true);
    }

    public function equipeUsada(Tenant $tenant): int
    {
        return $this->consulta($tenant)
            ->whereIn('role', array_map(fn (UserRole $papel): string => $papel->value, self::PAPEIS_DE_EQUIPE))
            ->count();
    }

    public function sociosUsados(Tenant $tenant): int
    {
        return $this->consulta($tenant)->where('role', UserRole::Jogador->value)->count();
    }

    /**
     * @return array{equipe_usada:int, equipe_limite:?int, socios_usados:int, socios_limite:?int}
     */
    public function resumo(Tenant $tenant): array
    {
        return [
            'equipe_usada' => $this->equipeUsada($tenant),
            'equipe_limite' => $tenant->limite_equipe,
            'socios_usados' => $this->sociosUsados($tenant),
            'socios_limite' => $tenant->limite_socios,
        ];
    }

    public function temVaga(Tenant $tenant, UserRole $papel): bool
    {
        if ($this->ehEquipe($papel)) {
            return $tenant->limite_equipe === null || $this->equipeUsada($tenant) < $tenant->limite_equipe;
        }

        return $tenant->limite_socios === null || $this->sociosUsados($tenant) < $tenant->limite_socios;
    }

    /**
     * @throws ValidationException quando o clube já usou todas as vagas daquele tipo
     */
    public function garantirVaga(Tenant $tenant, UserRole $papel): void
    {
        if ($this->temVaga($tenant, $papel)) {
            return;
        }

        $mensagem = $this->ehEquipe($papel)
            ? "Seu plano permite {$tenant->limite_equipe} usuário(s) de equipe (administrador, recepção e professor) e todos já estão em uso."
            : "Seu plano permite {$tenant->limite_socios} sócio(s) e todos já estão em uso.";

        throw ValidationException::withMessages([
            'limite' => $mensagem.' Fale com '.config('filaplay.contato_rotulo').' para ampliar.',
        ]);
    }

    private function consulta(Tenant $tenant): Builder
    {
        return User::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->id)
            ->where('super_admin', false);
    }
}
