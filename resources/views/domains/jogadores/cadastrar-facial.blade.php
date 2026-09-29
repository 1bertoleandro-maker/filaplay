<div class="mx-auto max-w-lg">
    <h1 class="text-3xl font-bold">Reconhecimento facial</h1>
    <p class="mt-2 text-lg text-muted">Olá, {{ $socio->nome }}! Abra a câmera, centralize o rosto e tire a foto para reservar no tablet do clube.</p>

    @if ($concluido)
        <p class="mt-8 rounded-2xl border border-brand/40 bg-brand/10 px-4 py-4 text-lg text-brand">
            Pronto! Seu reconhecimento facial foi salvo. Você já pode reservar no tablet.
        </p>
    @else
        <form wire:submit="salvar" class="mt-8 space-y-5">
            <x-camera-rosto model="foto" rotulo="Abrir minha câmera" />
            @error('foto') <p class="text-red-400">{{ $message }}</p> @enderror

            <button type="submit"
                    class="flex min-h-14 w-full cursor-pointer items-center justify-center gap-2 rounded-2xl bg-brand text-xl font-black text-canvas hover:brightness-110 active:scale-95">
                <x-heroicon-o-check class="h-6 w-6" />
                Salvar reconhecimento
            </button>
        </form>
    @endif
</div>
