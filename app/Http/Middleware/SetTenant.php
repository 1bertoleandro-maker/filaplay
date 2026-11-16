<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Clube\Enums\TenantStatus;
use App\Domains\Clube\Models\Tenant;
use App\Support\Contato;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class SetTenant
{
    /** Chave da sessão com o clube em que o super admin está trabalhando. */
    public const CLUBE_ATIVO = 'clube_ativo_id';

    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            // Quem já está logado perde o acesso quando o teste/plano vence ou o clube é bloqueado.
            if (! $user->super_admin) {
                $motivo = $this->motivoDeSemAcesso($user->tenant);

                if ($motivo !== null) {
                    Auth::guard()->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->with('status', $motivo);
                }
            }

            $tenantId = (int) $user->tenant_id;

            // Super admin entra em qualquer clube: nesta requisição ele "é" daquele clube
            // (sócios, fotos, quadras e importações vão para o clube escolhido).
            if ($user->super_admin && $request->hasSession() && $request->session()->has(self::CLUBE_ATIVO)) {
                $clube = Tenant::query()->find((int) $request->session()->get(self::CLUBE_ATIVO));

                if ($clube === null) {
                    $request->session()->forget(self::CLUBE_ATIVO);
                } else {
                    $tenantId = (int) $clube->id;
                    $user->setAttribute('tenant_id', $tenantId);
                    // Não grava a troca: o clube de origem do super admin continua o mesmo no banco.
                    $user->syncOriginalAttribute('tenant_id');
                    $user->setRelation('tenant', $clube);
                }
            }

            $this->tenantContext->set($tenantId);
        }

        try {
            return $next($request);
        } finally {
            $this->tenantContext->forget();
        }
    }

    private function motivoDeSemAcesso(?Tenant $tenant): ?string
    {
        if ($tenant === null) {
            return null;
        }

        if ($tenant->status === TenantStatus::Bloqueado) {
            return 'O acesso do seu clube está bloqueado. Fale com '.Contato::rotulo().'.';
        }

        if ($tenant->expirado()) {
            return $tenant->teste_gratis
                ? 'Seu teste grátis terminou. Fale com '.Contato::rotulo().' para contratar e continuar usando.'
                : 'O acesso do seu clube expirou. Fale com '.Contato::rotulo().' para renovar.';
        }

        return null;
    }
}
