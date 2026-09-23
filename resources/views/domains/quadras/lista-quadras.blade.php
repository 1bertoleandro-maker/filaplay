<div class="mx-auto max-w-6xl">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">Quadras</p>
            <h1 class="mt-2 text-4xl font-bold">Cadastro da quadra</h1>
            <p class="mt-2 text-lg text-muted">Foto, apelido e dados que aparecem no painel da TV.</p>
        </div>
        @if ($podeEditar)
            <button type="button" wire:click="nova" class="min-h-14 rounded-2xl border border-line px-6 text-lg font-semibold">
                Limpar formulário
            </button>
        @endif
    </div>

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-brand/40 bg-brand/10 px-4 py-3 text-lg text-brand">{{ session('status') }}</p>
    @endif

    @if ($formAberto)
        <form wire:submit="salvar" class="mt-8 rounded-2xl border border-line bg-card p-6">
            <h2 class="text-2xl font-semibold">{{ $quadraId ? 'Editar quadra' : 'Nova quadra' }}</h2>
            <div class="mt-6 grid gap-5 lg:grid-cols-[280px_1fr]">
                <div class="overflow-hidden rounded-2xl border border-line bg-canvas">
                    @if ($foto)
                        <img src="{{ $foto->temporaryUrl() }}" alt="Prévia da quadra" class="h-56 w-full object-cover">
                    @elseif ($quadraId && ($quadras->firstWhere('id', $quadraId)?->foto_path))
                        <img src="{{ $quadras->firstWhere('id', $quadraId)->fotoUrl }}" alt="Foto da quadra" class="h-56 w-full object-cover">
                    @else
                        <img src="{{ \App\Domains\Quadras\Models\Quadra::ilustracaoDoPiso($tipo_piso) }}" alt="Imagem padrão do piso" class="h-56 w-full object-cover">
                    @endif
                    <label class="block border-t border-line p-4">
                        <span class="text-lg text-muted">Foto da quadra (opcional)</span>
                        <p class="text-sm text-muted">Sem foto, a TV usa a imagem padrão do piso.</p>
                        <input type="file" wire:model="foto" accept="image/*" class="mt-2 w-full text-lg">
                        @error('foto') <span class="text-red-400">{{ $message }}</span> @enderror
                    </label>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <label class="block">
                        <span class="text-lg text-muted">Nome</span>
                        <input type="text" wire:model="nome" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg" placeholder="Quadra 1">
                        @error('nome') <span class="text-red-400">{{ $message }}</span> @enderror
                    </label>
                    <label class="block">
                        <span class="text-lg text-muted">Apelido na TV</span>
                        <input type="text" wire:model="apelido" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg" placeholder="Central">
                    </label>
                    <label class="block">
                        <span class="text-lg text-muted">Piso</span>
                        <select wire:model.live="tipo_piso" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                            @foreach ($pisos as $piso)
                                <option value="{{ $piso->value }}">{{ $piso->label() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-lg text-muted">Status</span>
                        <select wire:model="status" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                            @foreach ($statusList as $item)
                                <option value="{{ $item->value }}">{{ $item->label() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-lg text-muted">Ordem na TV</span>
                        <input type="number" wire:model="ordem_exibicao" min="1" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                    </label>
                    <div class="flex flex-col justify-end gap-3">
                        <label class="flex min-h-12 items-center gap-3 text-lg">
                            <input type="checkbox" wire:model="coberta" class="rounded border-line bg-canvas">
                            Coberta
                        </label>
                        <label class="flex min-h-12 items-center gap-3 text-lg">
                            <input type="checkbox" wire:model="iluminacao" class="rounded border-line bg-canvas">
                            Iluminação
                        </label>
                    </div>
                </div>
            </div>
            <button type="submit" class="mt-6 min-h-14 rounded-2xl bg-brand px-8 text-xl font-semibold text-canvas">Salvar quadra</button>
        </form>
    @endif

    <div class="mt-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($quadras as $quadra)
            <article class="overflow-hidden rounded-2xl border border-line bg-card">
                <div class="relative h-48 bg-canvas">
                    <img src="{{ $quadra->fotoUrl }}" alt="{{ $quadra->nome }}" class="h-full w-full object-cover">
                    <span class="absolute right-3 top-3 rounded-full bg-canvas/80 px-3 py-1 text-sm font-semibold">{{ $quadra->status->label() }}</span>
                    @if ($quadra->apelido)
                        <span class="absolute left-3 bottom-3 rounded-full bg-brand px-3 py-1 text-sm font-bold text-canvas">{{ $quadra->apelido }}</span>
                    @endif
                </div>
                <div class="p-5">
                    <h2 class="text-2xl font-bold">{{ $quadra->nome }}</h2>
                    <p class="mt-1 text-lg text-muted">
                        {{ $quadra->tipo_piso->label() }}
                        · {{ $quadra->coberta ? 'Coberta' : 'Descoberta' }}
                    </p>
                    @if ($podeEditar)
                        <button type="button" wire:click="editar({{ $quadra->id }})" class="mt-4 text-lg font-semibold text-brand">Editar foto e apelido</button>
                    @endif
                </div>
            </article>
        @empty
            <p class="text-xl text-muted">Nenhuma quadra cadastrada.</p>
        @endforelse
    </div>
</div>
