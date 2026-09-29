@props([
    'agenda',
    'podeReservar' => false,
])

@php
    $colunas = $agenda['colunas'];
    $total = max(1, count($colunas));
    $rotuloStatus = [
        'em_jogo' => 'Em jogo',
        'proximo' => 'A seguir',
        'livre' => 'Livre',
        'bloqueada' => 'Bloqueada',
    ];
@endphp

<style>
    @keyframes fp-pulse { 0%, 100% { opacity: 1; } 50% { opacity: .35; } }
    @keyframes fp-shine {
        0% { background-position: 0% 50%; }
        100% { background-position: 100% 50%; }
    }
    .fp-agenda {
        display: grid;
        grid-template-columns: repeat({{ $total }}, minmax(0, 1fr));
        gap: 0.85rem;
        height: 100%;
        min-height: 0;
        flex: 1;
        align-items: stretch;
    }
    @media (max-width: 1100px) {
        .fp-agenda {
            grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
            overflow-y: auto;
            align-content: start;
        }
    }
    .fp-court {
        position: relative;
        display: flex;
        flex-direction: column;
        min-height: 0;
        height: 100%;
        overflow: hidden;
        border-radius: 1.5rem;
        border: 1px solid rgba(255,255,255,.08);
        background:
            linear-gradient(180deg, rgba(26,29,35,.92) 0%, rgba(15,17,21,.98) 100%);
        box-shadow: 0 18px 40px rgba(0,0,0,.35);
        transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
    }
    .fp-court:hover { border-color: rgba(53,199,89,.45); transform: translateY(-2px); }
    .fp-court.is-live {
        border-color: rgba(53,199,89,.55);
        box-shadow: 0 0 0 1px rgba(53,199,89,.25), 0 20px 50px rgba(53,199,89,.12);
    }
    .fp-court.is-next { border-color: rgba(255,214,10,.4); }
    .fp-court.is-blocked { opacity: .88; filter: saturate(.7); }
    .fp-court-hero {
        position: relative;
        height: 9.5rem;
        flex-shrink: 0;
        overflow: hidden;
    }
    .fp-court-hero img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scale(1.04);
    }
    .fp-court-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(15,17,21,.15) 0%, rgba(15,17,21,.92) 100%);
    }
    .fp-badge {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        border-radius: 999px;
        padding: .4rem .85rem;
        font-size: .7rem;
        font-weight: 900;
        letter-spacing: .16em;
        text-transform: uppercase;
    }
    .fp-badge-live { background: #35c759; color: #0f1115; }
    .fp-badge-next { background: #ffd60a; color: #0f1115; }
    .fp-badge-free { background: rgba(255,255,255,.92); color: #0f1115; }
    .fp-badge-blocked { background: rgba(239,68,68,.2); color: #fca5a5; border: 1px solid rgba(239,68,68,.35); }
    .fp-dot { width: .55rem; height: .55rem; border-radius: 999px; background: #0f1115; animation: fp-pulse 1.3s ease-in-out infinite; }
    .fp-cta {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        min-height: 3.5rem;
        width: 100%;
        border-radius: 1rem;
        background: linear-gradient(90deg, #2db84f, #35c759, #4ade80);
        background-size: 200% 100%;
        color: #0f1115;
        font-weight: 900;
        font-size: .95rem;
        letter-spacing: .04em;
        cursor: pointer;
        box-shadow: 0 10px 28px rgba(53,199,89,.28);
        transition: filter .15s ease, transform .15s ease;
    }
    .fp-cta:hover { filter: brightness(1.08); animation: fp-shine 1.2s linear infinite; }
    .fp-cta:active { transform: scale(.97); }
    .fp-slot {
        display: flex;
        align-items: center;
        gap: .55rem;
        border-radius: .9rem;
        padding: .55rem .7rem;
        background: rgba(255,255,255,.04);
    }
    .fp-slot.is-now {
        background: rgba(53,199,89,.14);
        box-shadow: inset 0 0 0 1px rgba(53,199,89,.35);
    }
    .fp-slot.is-past { opacity: .38; }
</style>

@if ($colunas === [])
    <div class="flex h-full items-center justify-center rounded-3xl border border-dashed border-line text-lg text-muted">
        Cadastre as quadras para começar a agenda.
    </div>
@else
    <div class="fp-agenda">
        @foreach ($colunas as $coluna)
            @php
                $quadra = $coluna['quadra'];
                $status = $coluna['status'];
                $destaque = $coluna['atual'] ?? $coluna['proxima'] ?? null;
                $jogadores = $coluna['atual'] ? $coluna['jogadores_atual'] : $coluna['jogadores_proxima'];
                $ladoA = array_slice($jogadores, 0, (int) ceil(max(1, count($jogadores)) / 2));
                $ladoB = array_slice($jogadores, (int) ceil(max(1, count($jogadores)) / 2));
                $nomeQuadra = $quadra->apelido ?: $quadra->nome;
            @endphp

            <section @class([
                'fp-court',
                'is-live' => $status === 'em_jogo',
                'is-next' => $status === 'proximo',
                'is-blocked' => $status === 'bloqueada',
            ])>
                <div class="fp-court-hero">
                    <img src="{{ $quadra->fotoUrl }}" alt="">
                    <div class="absolute inset-x-0 bottom-0 z-10 flex items-end justify-between gap-2 px-3.5 pb-3">
                        <div class="min-w-0">
                            <p class="truncate text-xl font-black leading-none tracking-tight drop-shadow">{{ $nomeQuadra }}</p>
                            <p class="mt-1 truncate text-[10px] font-bold uppercase tracking-[0.18em] text-white/70">
                                {{ $quadra->tipo_piso?->label() }}{{ $quadra->coberta ? ' · Coberta' : '' }}
                            </p>
                        </div>
                        <span @class([
                            'fp-badge shrink-0',
                            'fp-badge-live' => $status === 'em_jogo',
                            'fp-badge-next' => $status === 'proximo',
                            'fp-badge-free' => $status === 'livre',
                            'fp-badge-blocked' => $status === 'bloqueada',
                        ])>
                            @if ($status === 'em_jogo') <span class="fp-dot"></span> @endif
                            {{ $rotuloStatus[$status] }}
                        </span>
                    </div>
                </div>

                <div class="flex min-h-0 flex-1 flex-col px-3.5 pb-3.5 pt-3">
                    <div class="flex min-h-[9.5rem] flex-col items-center justify-center rounded-2xl bg-black/25 px-2 py-3 ring-1 ring-white/5">
                        @if ($status === 'bloqueada')
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-red-500/15 text-red-300 ring-1 ring-red-400/30">
                                <x-heroicon-o-lock-closed class="h-7 w-7" />
                            </div>
                            <p class="mt-3 text-base font-black">Indisponível agora</p>
                            <p class="mt-1 text-center text-xs text-muted">Bloqueio ativo nesta faixa</p>

                        @elseif ($destaque && $jogadores !== [])
                            <div class="flex w-full items-center justify-center gap-2">
                                <div class="flex -space-x-2">
                                    @foreach ($ladoA as $jogador)
                                        @include('components.agenda-jogador-bolha', ['jogador' => $jogador])
                                    @endforeach
                                </div>
                                @if ($ladoB !== [])
                                    <span class="rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-black uppercase tracking-[0.2em] text-muted">VS</span>
                                    <div class="flex -space-x-2">
                                        @foreach ($ladoB as $jogador)
                                            @include('components.agenda-jogador-bolha', ['jogador' => $jogador])
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <p class="mt-3 w-full truncate text-center text-sm font-bold">
                                {{ collect($ladoA)->pluck('primeiro_nome')->join(' · ') }}
                                @if ($ladoB !== [])
                                    <span class="text-muted"> × </span>
                                    {{ collect($ladoB)->pluck('primeiro_nome')->join(' · ') }}
                                @endif
                            </p>
                            <p class="mt-1 text-sm font-black tabular-nums text-brand">
                                {{ $destaque->inicio->format('H:i') }} – {{ $destaque->fim->format('H:i') }}
                            </p>

                            @if ($status === 'em_jogo' && $coluna['restante'] !== null)
                                <div class="mt-3 w-full px-1" x-data="{
                                    restante: {{ (int) $coluna['restante'] }},
                                    duracao: {{ max(1, (int) $coluna['duracao']) }},
                                    texto() {
                                        const m = Math.floor(Math.max(0, this.restante) / 60);
                                        const s = String(Math.max(0, this.restante) % 60).padStart(2, '0');
                                        return m + ':' + s;
                                    },
                                    pct() { return Math.max(4, Math.min(100, (Math.max(0, this.restante) / this.duracao) * 100)); }
                                }" x-init="setInterval(() => { if (restante > 0) restante-- }, 1000)">
                                    <p class="text-center text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">
                                        Restante <span class="tabular-nums text-brand" x-text="texto()"></span>
                                    </p>
                                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-line">
                                        <div class="h-full rounded-full bg-brand transition-all duration-1000" :style="`width:${pct()}%`"></div>
                                    </div>
                                </div>
                            @elseif ($status === 'proximo' && $coluna['proxima'])
                                <p class="mt-3 text-xs font-bold text-accent">Entra às {{ $coluna['proxima']->inicio->format('H:i') }}</p>
                            @endif
                        @else
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-brand/15 text-brand ring-1 ring-brand/30">
                                <x-heroicon-o-check class="h-7 w-7" />
                            </div>
                            <p class="mt-3 text-base font-black">Quadra livre</p>
                            <p class="mt-1 text-center text-xs text-muted">Toque em Reservar para garantir o horário</p>
                        @endif
                    </div>

                    <div class="mt-3 flex min-h-0 flex-1 flex-col">
                        <div class="mb-2 flex items-center justify-between px-0.5">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-muted">Fila do dia</p>
                            <p class="text-[10px] font-bold tabular-nums text-muted">{{ count($coluna['timeline']) }}</p>
                        </div>
                        <div class="min-h-0 flex-1 space-y-1.5 overflow-y-auto pr-0.5">
                            @forelse ($coluna['timeline'] as $bloco)
                                <div @class([
                                    'fp-slot',
                                    'is-now' => $bloco['atual'],
                                    'is-past' => $bloco['passado'],
                                ])>
                                    <span class="w-[4.6rem] shrink-0 text-[11px] font-black tabular-nums text-accent">
                                        {{ $bloco['inicio'] }}–{{ $bloco['fim'] }}
                                    </span>
                                    <span class="flex min-w-0 flex-1 items-center gap-1">
                                        @foreach (array_slice($bloco['jogadores'], 0, 4) as $jogador)
                                            @if ($jogador['foto'])
                                                <img src="{{ $jogador['foto'] }}" alt="" class="h-6 w-6 rounded-full object-cover ring-1 ring-canvas">
                                            @else
                                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand text-[9px] font-black text-canvas">{{ $jogador['iniciais'] }}</span>
                                            @endif
                                        @endforeach
                                        <span class="truncate text-[11px] font-semibold">{{ collect($bloco['jogadores'])->pluck('primeiro_nome')->join(', ') }}</span>
                                    </span>
                                </div>
                            @empty
                                <div class="flex h-full min-h-[4.5rem] items-center justify-center rounded-2xl border border-dashed border-white/10 text-xs text-muted">
                                    Sem reservas na fila
                                </div>
                            @endforelse
                        </div>
                    </div>

                    @if ($podeReservar)
                        <div class="mt-3 shrink-0">
                            @if ($coluna['pode_reservar_agora'] && $status !== 'bloqueada')
                                <button
                                    type="button"
                                    wire:key="reservar-{{ $quadra->id }}"
                                    wire:click.prevent="reservarQuadra({{ $quadra->id }})"
                                    class="fp-cta relative z-10"
                                >
                                    <x-heroicon-o-plus class="h-5 w-5" />
                                    Reservar
                                </button>
                            @else
                                <div class="flex min-h-14 items-center justify-center rounded-2xl border border-white/10 text-sm font-bold text-muted">
                                    {{ $status === 'bloqueada' ? 'Bloqueada agora' : 'Sem horário hoje' }}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </section>
        @endforeach
    </div>
@endif
