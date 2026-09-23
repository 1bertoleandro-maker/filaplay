<div>
    <h1 class="text-2xl font-bold">Olá, {{ $socio->nome }}!</h1>
    <p class="mt-2 text-lg text-muted">
        @if ($this->precisaDeFoto())
            Falta pouco. Crie sua senha e envie uma foto do seu rosto para concluir o cadastro no
        @else
            Falta pouco. Crie uma senha para acessar o painel do
        @endif
        <span class="font-semibold text-white">{{ $socio->tenant->nome }}</span>.
    </p>

    <form wire:submit="confirmar" class="mt-8 space-y-5">
        <label class="block">
            <span class="text-lg text-muted">Crie uma senha</span>
            <input type="password" wire:model="senha" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
            @error('senha') <span class="text-red-400">{{ $message }}</span> @enderror
        </label>
        <label class="block">
            <span class="text-lg text-muted">Confirme a senha</span>
            <input type="password" wire:model="senha_confirmation" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
        </label>

        <label class="block">
            <span class="text-lg text-muted">
                Foto do seu rosto (para reconhecimento facial)
                @unless ($this->precisaDeFoto()) <span class="text-muted">— opcional</span> @endunless
            </span>
            <input type="file" wire:model="foto" accept="image/*" capture="user" class="mt-2 w-full text-lg">
            @error('foto') <span class="text-red-400">{{ $message }}</span> @enderror
        </label>

        @if ($foto)
            <img src="{{ $foto->temporaryUrl() }}" alt="Prévia" class="mx-auto h-32 w-32 rounded-full object-cover ring-2 ring-brand">
        @endif

        <button type="submit" class="min-h-14 w-full rounded-2xl bg-brand text-xl font-semibold text-canvas">
            Concluir cadastro e entrar
        </button>
    </form>
</div>
