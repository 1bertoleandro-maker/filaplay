<div class="mx-auto max-w-7xl" x-data="{ modalAberto: @entangle('formAberto'), detalheAberto: @entangle('reservaSelecionadaId').live }">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">Reservas</p>
            <h1 class="mt-2 text-4xl font-bold">Marcar horário</h1>
            <p class="mt-2 text-lg text-muted">Clique num quadrinho livre para reservar. Simples assim.</p>
        </div>
        <button type="button" wire:click="nova" class="min-h-14 rounded-2xl bg-brand px-6 text-lg font-semibold text-canvas">
            + Nova reserva
        </button>
    </div>

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-brand/40 bg-brand/10 px-4 py-3 text-lg text-brand">{{ session('status') }}</p>
    @endif

    {{-- Navegação de data --}}
    <div class="mt-8 flex items-center justify-between gap-3 rounded-2xl border border-line bg-card px-4 py-3">
        <button type="button" wire:click="diaAnterior" class="flex h-12 w-12 items-center justify-center rounded-xl bg-canvas text-2xl font-bold hover:bg-line">‹</button>
        <div class="flex items-center gap-3">
            <span class="text-2xl font-black">{{ $diaLabel }}</span>
            <input type="date" wire:model.live="data" class="rounded-xl border-line bg-canvas px-3 py-2 text-base">
            @if ($diaLabel !== 'Hoje')
                <button type="button" wire:click="hoje" class="rounded-xl border border-brand px-3 py-2 text-sm font-semibold text-brand">Hoje</button>
            @endif
        </div>
        <button type="button" wire:click="diaSeguinte" class="flex h-12 w-12 items-center justify-center rounded-xl bg-canvas text-2xl font-bold hover:bg-line">›</button>
    </div>

    {{-- Legenda --}}
    <div class="mt-4 flex flex-wrap items-center gap-5 text-base text-muted">
        <span class="flex items-center gap-2"><span class="h-4 w-4 rounded-md border-2 border-dashed border-line"></span> Livre</span>
        <span class="flex items-center gap-2"><span class="h-4 w-4 rounded-md bg-brand"></span> Reservada</span>
        <span class="flex items-center gap-2"><span class="h-4 w-4 rounded-md bg-line/80"></span> Bloqueada</span>
    </div>

    @if ($fechado)
        <p class="mt-6 rounded-2xl border border-accent/40 bg-accent/10 px-4 py-4 text-lg text-accent">O clube está fechado neste dia.</p>
    @else
        {{-- GRADE VISUAL: quadras x horários --}}
        <div class="mt-4 overflow-x-auto rounded-2xl border border-line bg-card">
            <div class="inline-grid min-w-full" style="grid-template-columns: 84px repeat({{ count($quadras) }}, minmax(140px, 1fr));">
                {{-- Cabeçalho --}}
                <div class="sticky left-0 top-0 z-20 border-b border-r border-line bg-card"></div>
                @foreach ($quadras as $quadra)
                    <div class="sticky top-0 z-10 border-b border-line bg-card px-3 py-3 text-center">
                        <p class="truncate text-lg font-bold">{{ $quadra->apelido ?: $quadra->nome }}</p>
                        <p class="text-xs text-muted">{{ $quadra->tipo_piso?->label() }}</p>
                    </div>
                @endforeach

                {{-- Linhas de horário --}}
                @foreach ($slots as $slot)
                    <div class="sticky left-0 z-10 flex items-center justify-center border-r border-t border-line bg-card px-2 py-3 text-base font-bold tabular-nums">
                        {{ $slot }}
                    </div>
                    @foreach ($quadras as $quadra)
                        @php $cel = $grade[$quadra->id][$slot]; @endphp
                        <div class="border-t border-line p-1.5">
                            @if ($cel['status'] === 'livre')
                                <button type="button" wire:click="abrirSlot({{ $quadra->id }}, '{{ $slot }}')"
                                    class="group flex min-h-16 w-full items-center justify-center rounded-xl border-2 border-dashed border-line text-muted transition hover:border-brand hover:bg-brand/10 hover:text-brand">
                                    <x-heroicon-o-plus class="h-6 w-6 opacity-0 transition group-hover:opacity-100" />
                                </button>
                            @elseif ($cel['status'] === 'ocupada')
                                <button type="button" wire:click="verDetalhe({{ $cel['reserva']->id }})"
                                    class="flex min-h-16 w-full flex-col items-center justify-center gap-0.5 rounded-xl bg-brand px-1 text-center text-canvas transition hover:brightness-110">
                                    <span class="truncate text-sm font-bold leading-tight">{{ $cel['reserva']->user->nome }}</span>
                                    <span class="text-[11px] opacity-80">{{ $cel['reserva']->status->label() }}</span>
                                </button>
                            @elseif ($cel['status'] === 'bloqueada')
                                <div class="flex min-h-16 w-full flex-col items-center justify-center gap-0.5 rounded-xl border border-line bg-line/40 px-1 text-center">
                                    <x-heroicon-o-lock-closed class="h-4 w-4 text-muted" />
                                    <span class="truncate text-xs font-semibold text-muted">{{ $cel['bloqueio']->motivo ?: 'Bloqueado' }}</span>
                                </div>
                            @else
                                <div class="min-h-16 w-full rounded-xl bg-canvas/40"></div>
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    @endif

    {{-- MODAL: nova reserva --}}
    <div x-show="modalAberto" x-cloak style="display: none;" class="fixed inset-0 z-40 flex items-center justify-center bg-black/70 p-4">
        <div @click.outside="$wire.fechar()" class="w-full max-w-lg rounded-3xl border border-line bg-card p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold">Reservar quadra</h2>
                <button type="button" wire:click="fechar" class="text-muted hover:text-white">✕</button>
            </div>

            <form wire:submit="salvar" class="mt-6 space-y-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <label class="block">
                        <span class="text-lg text-muted">Quadra</span>
                        <select wire:model="quadra_id" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                            @foreach ($quadras as $quadra)
                                <option value="{{ $quadra->id }}">{{ $quadra->apelido ?: $quadra->nome }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-lg text-muted">Horário</span>
                        <select wire:model="hora" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                            @forelse ($slots as $slot)
                                <option value="{{ $slot }}">{{ $slot }}</option>
                            @empty
                                <option value="">Sem horários</option>
                            @endforelse
                        </select>
                        @error('hora') <span class="text-red-400">{{ $message }}</span> @enderror
                    </label>
                </div>

                <label class="block">
                    <span class="text-lg text-muted">Sócio</span>
                    <select wire:model="user_id" class="mt-2 w-full rounded-xl border-line bg-canvas text-lg">
                        <option value="">Selecione</option>
                        @foreach ($socios as $socio)
                            <option value="{{ $socio->id }}">{{ $socio->nome }} · {{ $socio->matricula }}</option>
                        @endforeach
                    </select>
                    @error('user_id') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>

                <fieldset>
                    <legend class="text-lg text-muted">Como validar</legend>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-xl border border-line bg-canvas px-4 text-base has-[:checked]:border-brand has-[:checked]:bg-brand/10">
                            <input type="radio" wire:model.live="validacao" value="secretaria" class="text-brand">
                            Secretaria confirma
                        </label>
                        <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-xl border border-line bg-canvas px-4 text-base has-[:checked]:border-brand has-[:checked]:bg-brand/10">
                            <input type="radio" wire:model.live="validacao" value="facial" class="text-brand">
                            Reconhecimento facial
                        </label>
                    </div>
                </fieldset>

                @if ($validacao === 'facial')
                    <label class="block">
                        <span class="text-lg text-muted">Foto do rosto agora</span>
                        <input type="file" wire:model="foto_facial" accept="image/*" class="mt-2 w-full text-lg">
                        @error('foto_facial') <span class="text-red-400">{{ $message }}</span> @enderror
                    </label>
                @endif

                <button type="submit" class="min-h-14 w-full rounded-2xl bg-brand text-xl font-semibold text-canvas">
                    Confirmar reserva
                </button>
            </form>
        </div>
    </div>

    {{-- MODAL: detalhe / cancelar --}}
    <div x-show="detalheAberto" x-cloak style="display: none;" class="fixed inset-0 z-40 flex items-center justify-center bg-black/70 p-4">
        <div @click.outside="$wire.fecharDetalhe()" class="w-full max-w-md rounded-3xl border border-line bg-card p-6 shadow-2xl">
            @if ($reservaSelecionada)
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold">{{ $reservaSelecionada->quadra->apelido ?: $reservaSelecionada->quadra->nome }}</h2>
                    <button type="button" wire:click="fecharDetalhe" class="text-muted hover:text-white">✕</button>
                </div>
                <p class="mt-4 text-xl font-semibold">{{ $reservaSelecionada->user->nome }}</p>
                <p class="text-muted">Matrícula {{ $reservaSelecionada->user->matricula }}</p>
                <p class="mt-3 text-lg">{{ $reservaSelecionada->inicio->format('d/m/Y H:i') }} – {{ $reservaSelecionada->fim->format('H:i') }}</p>
                <p class="mt-1 font-semibold text-brand">{{ $reservaSelecionada->status->label() }}</p>
                @if ($reservaSelecionada->observacoes)
                    <p class="mt-2 text-sm text-muted">{{ $reservaSelecionada->observacoes }}</p>
                @endif

                @if ($reservaSelecionada->status->value === 'confirmada')
                    <button type="button" wire:click="cancelar({{ $reservaSelecionada->id }})" wire:confirm="Cancelar esta reserva?"
                        class="mt-6 min-h-14 w-full rounded-2xl border-2 border-red-400 text-lg font-semibold text-red-400 hover:bg-red-400/10">
                        Cancelar reserva
                    </button>
                @endif
            @endif
        </div>
    </div>
</div>
