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

        <div>
            <p class="mb-2 text-lg font-bold">
                Foto do seu rosto (reconhecimento facial)
                @unless ($this->precisaDeFoto()) <span class="font-normal text-muted">— opcional</span> @endunless
            </p>
            <x-camera-rosto model="foto" rotulo="Abrir minha câmera" />
            @error('foto') <p class="mt-2 text-red-400">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
                class="flex min-h-14 w-full cursor-pointer items-center justify-center gap-2 rounded-2xl bg-brand text-xl font-black text-canvas hover:brightness-110 active:scale-95">
            <x-heroicon-o-check class="h-6 w-6" />
            Concluir cadastro e entrar
        </button>
    </form>
</div>
