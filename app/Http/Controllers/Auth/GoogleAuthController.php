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
        $state = (string) request()->query('state', '');
        if (str_starts_with($state, 'af.') && request()->filled('code')) {
            $alvo = $this->retornoAlcoolicos($state);
            if ($alvo === null) {
                return redirect()->route('login')->withErrors([
                    'google' => 'Não consegui devolver o login para o Alcoólicos Finals.',
                ]);
            }

            return redirect()->away($alvo.'?'.http_build_query([
                'code' => (string) request()->query('code'),
                'state' => $state,
            ]));
        }

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

    private function retornoAlcoolicos(string $state): ?string
    {
        $resto = substr($state, 3);
        $ponto = strpos($resto, '.');
        if ($ponto === false) {
            return null;
        }
        $url = base64_decode(strtr(substr($resto, 0, $ponto), '-_', '+/'), true);
        if (! is_string($url) || $url === '') {
            return null;
        }
        $partes = parse_url($url);
        $host = strtolower((string) ($partes['host'] ?? ''));
        $scheme = (string) ($partes['scheme'] ?? '');
        $path = (string) ($partes['path'] ?? '');
        $permitido = ($scheme === 'https' && $host === 'alcoolicosfinals.com.br')
            || ($scheme === 'http' && in_array($host, ['localhost', '127.0.0.1'], true));
        if (! $permitido || ! str_ends_with($path, '/auth/google/callback')) {
            return null;
        }
        if (isset($partes['query']) || isset($partes['user']) || isset($partes['pass'])) {
            return null;
        }

        return $scheme.'://'.$host.(isset($partes['port']) ? ':'.$partes['port'] : '').$path;
    }
}
