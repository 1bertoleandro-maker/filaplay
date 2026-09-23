<?php

declare(strict_types=1);

namespace App\Domains\Jogadores\Actions;

use App\Domains\Clube\Enums\TenantStatus;
use App\Domains\Jogadores\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class EnsureUserCanAccess
{
    public function handle(User $user): void
    {
        if ($user->super_admin) {
            return;
        }

        if (! $user->podeAcessar()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'form.email' => 'Seu acesso está bloqueado. Procure a recepção.',
            ]);
        }

        $tenant = $user->tenant;
        $status = $tenant?->status;

        if ($status === TenantStatus::Pendente) {
            Auth::logout();

            throw ValidationException::withMessages([
                'form.email' => 'Seu clube ainda está em análise pela equipe FilaPlay. Avisamos por e-mail quando for liberado.',
            ]);
        }

        if ($status === TenantStatus::Bloqueado) {
            Auth::logout();

            throw ValidationException::withMessages([
                'form.email' => 'O acesso do seu clube está temporariamente bloqueado. Fale com o suporte FilaPlay.',
            ]);
        }

        if ($tenant?->expirado() === true) {
            Auth::logout();

            throw ValidationException::withMessages([
                'form.email' => 'O acesso do seu clube expirou. Fale com o suporte FilaPlay para renovar.',
            ]);
        }
    }
}
