<div>
    @if ($enviado)
        <div class="text-center">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand/15 text-brand">
                <x-heroicon-o-check-circle class="h-10 w-10" />
            </span>
            <h1 class="mt-4 text-2xl font-bold">Cadastro enviado!</h1>
            <p class="mt-3 text-lg text-muted">
                Nossa equipe vai analisar os dados do seu clube. Você recebe um e-mail assim que o acesso for liberado.
            </p>
            <a href="{{ route('login') }}" class="mt-6 inline-flex min-h-14 items-center justify-center rounded-2xl bg-brand px-8 text-lg font-semibold text-canvas">
                Voltar para o login
            </a>
        </div>
    @else
        <h1 class="text-2xl font-bold">Cadastre seu clube</h1>
        <p class="mt-2 text-lg text-muted">Leva menos de 1 minuto. Depois é só aguardar a liberação.</p>

        @if (! $google_id)
            <a href="{{ route('auth.google.redirect') }}"
               class="mt-6 flex min-h-14 items-center justify-center gap-3 rounded-2xl border border-line bg-white text-lg font-semibold text-canvas">
                <svg class="h-6 w-6" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.9 32.7 29.4 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.5 6.1 29.5 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.7-.4-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 16 19 13 24 13c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.5 6.1 29.5 4 24 4c-7.7 0-14.3 4.4-17.7 10.7z"/><path fill="#4CAF50" d="M24 44c5.3 0 10.1-1.8 13.8-5l-6.4-5.4C29.4 35.4 26.8 36 24 36c-5.4 0-9.9-3.3-11.3-8l-6.6 5.1C9.7 39.6 16.3 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.3 4.3-4.2 5.6l6.4 5.4C41.5 35.3 44 30.1 44 24c0-1.3-.1-2.7-.4-3.5z"/></svg>
                Continuar com Google
            </a>
            <div class="my-6 flex items-center gap-3 text-muted">
                <span class="h-px flex-1 bg-line"></span>
                <span class="text-sm">ou preencha o formulário</span>
                <span class="h-px flex-1 bg-line"></span>
            </div>
        @else
            <p class="mt-4 rounded-2xl border border-brand/40 bg-brand/10 px-4 py-3 text-lg text-brand">
                Conectado com o Google como {{ $email }}
            </p>
        @endif

        <form wire:submit="salvar" class="mt-2 space-y-5">
            <label class="block">
                <span class="text-lg text-muted">Nome do clube / empresa</span>
                <input type="text" wire:model="nome_clube" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                @error('nome_clube') <span class="text-red-400">{{ $message }}</span> @enderror
            </label>
            <label class="block">
                <span class="text-lg text-muted">Seu nome</span>
                <input type="text" wire:model="nome_responsavel" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                @error('nome_responsavel') <span class="text-red-400">{{ $message }}</span> @enderror
            </label>
            <label class="block">
                <span class="text-lg text-muted">E-mail</span>
                <input type="email" wire:model="email" @disabled($google_id) class="mt-2 w-full rounded-xl border-line bg-canvas text-lg disabled:opacity-60">
                @error('email') <span class="text-red-400">{{ $message }}</span> @enderror
            </label>
            <label class="block">
                <span class="text-lg text-muted">Telefone / WhatsApp</span>
                <input type="text" wire:model="telefone" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
            </label>

            @unless ($google_id)
                <label class="block">
                    <span class="text-lg text-muted">Senha</span>
                    <input type="password" wire:model="senha" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                    @error('senha') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-lg text-muted">Confirmar senha</span>
                    <input type="password" wire:model="senha_confirmation" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                </label>
            @endunless

            <button type="submit" class="min-h-14 w-full rounded-2xl bg-brand text-xl font-semibold text-canvas">
                Cadastrar meu clube
            </button>
        </form>

        <p class="mt-6 text-center text-lg text-muted">
            Já tem conta? <a href="{{ route('login') }}" class="font-semibold text-brand">Entrar</a>
        </p>
    @endif
</div>
