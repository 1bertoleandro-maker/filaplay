<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domains\Jogadores\Actions\EnsureUserCanAccess;
use App\Domains\Jogadores\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

/**
 * Login social com Google. Se o e-mail já existe (equipe do clube ou sócio),
 * autentica direto. Se não existe, encaminha para o cadastro de um novo clube
 * — que nasce pendente até o super admin liberar.
 */
final class GoogleAuthController
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $usuario = User::query()->where('email', $googleUser->getEmail())->first();

        if ($usuario === null) {
            return redirect()
                ->route('clube.cadastrar')
                ->with('google_nome', (string) $googleUser->getName())
                ->with('google_email', (string) $googleUser->getEmail())
                ->with('google_id', (string) $googleUser->getId());
        }

        if ($usuario->google_id === null) {
            $usuario->forceFill(['google_id' => $googleUser->getId()])->save();
        }

        try {
            app(EnsureUserCanAccess::class)->handle($usuario);
        } catch (ValidationException $e) {
            return redirect()->route('login')->withErrors($e->errors());
        }

        Auth::login($usuario, true);
        Session::regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
