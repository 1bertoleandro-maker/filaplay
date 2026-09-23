<div class="mx-auto max-w-6xl">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">Bloqueios</p>
            <h1 class="mt-2 text-4xl font-bold">Torneios, aulas e manutenção</h1>
            <p class="mt-2 text-lg text-muted">Durante o bloqueio a quadra fica indisponível para fila e reservas.</p>
        </div>
        <button type="button" wire:click="nova" class="min-h-14 rounded-2xl bg-brand px-6 text-lg font-semibold text-canvas">
            Novo bloqueio
        </button>
    </div>

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-brand/40 bg-brand/10 px-4 py-3 text-lg text-brand">{{ session('status') }}</p>
    @endif

    @if ($formAberto)
        <form wire:submit="salvar" class="mt-8 rounded-2xl border border-line bg-card p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold">Cadastrar bloqueio</h2>
                <button type="button" wire:click="fechar" class="text-muted">Fechar</button>
            </div>
            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <label class="block">
                    <span class="text-lg text-muted">Quadra</span>
                    <select wire:model="quadra_id" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                        @foreach ($quadras as $quadra)
                            <option value="{{ $quadra->id }}">{{ $quadra->nome }}</option>
                        @endforeach
                    </select>
                    @error('quadra_id') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-lg text-muted">Motivo</span>
                    <select wire:model="tipo" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                        @foreach ($tipos as $t)
                            <option value="{{ $t->value }}">{{ $t->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-lg text-muted">Data</span>
                    <input type="date" wire:model="data" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                    @error('data') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block">
                        <span class="text-lg text-muted">Início</span>
                        <input type="time" wire:model="hora_inicio" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                    </label>
                    <label class="block">
                        <span class="text-lg text-muted">Fim</span>
                        <input type="time" wire:model="hora_fim" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                    </label>
                </div>
                <label class="block sm:col-span-2">
                    <span class="text-lg text-muted">Justificativa (aparece na TV)</span>
                    <input type="text" wire:model="motivo" placeholder="Ex.: Aula de tênis - Professor Marcos" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                    @error('motivo') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="flex min-h-14 items-center gap-3 rounded-xl border border-line bg-canvas px-4 text-lg sm:col-span-2">
                    <input type="checkbox" wire:model.live="recorrente" class="h-5 w-5 text-brand">
                    Repetir toda semana neste mesmo horário
                </label>
                @if ($recorrente)
                    <label class="block sm:col-span-2">
                        <span class="text-lg text-muted">Por quantas semanas?</span>
                        <input type="number" min="1" max="52" wire:model="semanas" class="mt-2 w-32 rounded-xl border-line bg-canvas text-lg">
                    </label>
                @endif
            </div>
            <button type="submit" class="mt-6 min-h-14 rounded-2xl bg-brand px-8 text-xl font-semibold text-canvas">Salvar bloqueio</button>
        </form>
    @endif

    <div class="mt-8 space-y-3">
        @forelse ($bloqueios as $bloqueio)
            <article class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-card px-5 py-4">
                <div>
                    <p class="text-xl font-semibold">{{ $bloqueio->quadra->nome }} · {{ $bloqueio->tipo->label() }}</p>
                    <p class="text-muted">{{ $bloqueio->inicio->format('d/m H:i') }} – {{ $bloqueio->fim->format('H:i') }} · {{ $bloqueio->motivo }}</p>
                </div>
                <button type="button" wire:click="remover({{ $bloqueio->id }})" wire:confirm="Remover este bloqueio?" class="font-semibold text-red-400">Remover</button>
            </article>
        @empty
            <p class="text-xl text-muted">Nenhum bloqueio futuro.</p>
        @endforelse
    </div>
</div>
