<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard'), navigate: true);
    }
}; ?>

<div>
    <h1 class="text-3xl font-bold">Entrar</h1>
    <p class="mt-2 text-lg text-muted">Acesse o painel do seu clube.</p>

    <!-- Session Status -->
    <x-auth-session-status class="mt-4" :status="session('status')" />

    <a href="{{ route('auth.google.redirect') }}"
       class="mt-6 flex min-h-14 items-center justify-center gap-3 rounded-2xl border border-line bg-white text-lg font-semibold text-canvas">
        <svg class="h-6 w-6" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.9 32.7 29.4 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.5 6.1 29.5 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.7-.4-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 16 19 13 24 13c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.5 6.1 29.5 4 24 4c-7.7 0-14.3 4.4-17.7 10.7z"/><path fill="#4CAF50" d="M24 44c5.3 0 10.1-1.8 13.8-5l-6.4-5.4C29.4 35.4 26.8 36 24 36c-5.4 0-9.9-3.3-11.3-8l-6.6 5.1C9.7 39.6 16.3 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.3 4.3-4.2 5.6l6.4 5.4C41.5 35.3 44 30.1 44 24c0-1.3-.1-2.7-.4-3.5z"/></svg>
        Entrar com Google
    </a>

    <div class="my-6 flex items-center gap-3 text-muted">
        <span class="h-px flex-1 bg-line"></span>
        <span class="text-sm">ou com e-mail e senha</span>
        <span class="h-px flex-1 bg-line"></span>
    </div>

    <form wire:submit="login" class="space-y-5">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="E-mail" class="text-lg" />
            <x-text-input wire:model="form.email" id="email" class="mt-2 block w-full" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Senha" class="text-lg" />

            <x-text-input wire:model="form.password" id="password" class="mt-2 block w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <label for="remember" class="flex items-center gap-2">
            <input wire:model="form.remember" id="remember" type="checkbox" class="h-5 w-5 rounded border-line bg-card text-brand" name="remember">
            <span class="text-lg text-muted">Lembrar de mim</span>
        </label>

        <div class="flex items-center justify-between gap-3">
            @if (Route::has('password.request'))
                <a class="text-lg text-muted underline" href="{{ route('password.request') }}" wire:navigate>
                    Esqueceu a senha?
                </a>
            @endif

            <x-primary-button class="px-8 text-lg">
                Entrar
            </x-primary-button>
        </div>
    </form>

    <p class="mt-8 text-center text-lg text-muted">
        Seu clube ainda não usa o FilaPlay?
        <a href="{{ route('clube.cadastrar') }}" class="font-semibold text-brand">Cadastre aqui</a>
    </p>
</div>
