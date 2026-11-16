<?php

declare(strict_types=1);

namespace App\Domains\Clube\Http;

use App\Domains\Clube\Models\Tenant;
use App\Http\Middleware\SetTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * O super admin escolhe em qual clube vai trabalhar (modo administrador daquele clube).
 */
final class ClubeAtivoController
{
    public function entrar(Request $request, int $tenant): RedirectResponse
    {
        abort_unless((bool) $request->user()?->super_admin, 403);

        $clube = Tenant::query()->findOrFail($tenant);

        $request->session()->put(SetTenant::CLUBE_ATIVO, $clube->id);

        return redirect()->route('dashboard')->with('status', 'Você está no clube '.$clube->nome.'. Tudo que cadastrar agora vai para ele.');
    }

    public function sair(Request $request): RedirectResponse
    {
        abort_unless((bool) $request->user()?->super_admin, 403);

        $request->session()->forget(SetTenant::CLUBE_ATIVO);

        return redirect()->route('admin.clubes');
    }
}
