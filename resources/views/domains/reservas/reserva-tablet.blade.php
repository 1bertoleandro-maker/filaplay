<div
    class="fp-shell"
    x-data="{ linha: window.innerWidth >= 1280 }"
    @if (! $painelAberto && $mensagem === '') wire:poll.10s @endif
>
<style>
    .fp-shell {
        min-height: 100vh;
        height: 100vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        color: #fff;
        background:
            radial-gradient(ellipse 70% 45% at 15% -10%, rgba(53,199,89,.18), transparent 50%),
            radial-gradient(ellipse 50% 40% at 95% 110%, rgba(255,214,10,.07), transparent 45%),
            #0b0d11;
    }
    .fp-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .85rem 1.25rem;
        flex-shrink: 0;
        flex-wrap: wrap;
    }
    .fp-main {
        flex: 1;
        min-height: 0;
        display: flex;
    }
    .fp-stage {
        flex: 1;
        min-width: 0;
        min-height: 0;
        padding: 0 1rem 1rem;
        display: flex;
        flex-direction: column;
    }
    .fp-aside {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        z-index: 35;
        width: min(22rem, 92vw);
        display: flex;
        flex-direction: column;
        background: #12151b;
        border-left: 1px solid rgba(255,255,255,.08);
        box-shadow: -20px 0 60px rgba(0,0,0,.5);
    }
    @media (min-width: 1280px) {
        .fp-aside {
            position: static;
            width: 22rem;
            flex-shrink: 0;
            margin: 0 1rem 1rem 0;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 1.5rem;
            box-shadow: none;
            overflow: hidden;
        }
    }
    .fp-event {
        border-radius: 1rem;
        padding: .7rem .8rem;
        background: rgba(255,255,255,.04);
        border-left: 4px solid rgba(255,255,255,.2);
    }
    .fp-event.is-new { animation: fp-flash 2.4s ease-out 1; }
    @keyframes fp-flash {
        0% { background: rgba(53,199,89,.45); }
        100% { background: rgba(255,255,255,.04); }
    }
    .fp-overlay {
        position: fixed;
        inset: 0;
        z-index: 40;
        display: flex;
        background: rgba(5,7,10,.72);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    .fp-sheet {
        margin: auto;
        width: min(1120px, 100%);
        height: min(920px, 100%);
        max-height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-radius: 1.75rem;
        border: 1px solid rgba(255,255,255,.1);
        background: linear-gradient(165deg, #1c212b 0%, #12151b 55%, #0f1115 100%);
        box-shadow: 0 40px 100px rgba(0,0,0,.55);
    }
    @media (max-width: 900px) {
        .fp-sheet {
            width: 100%;
            height: 100%;
            border-radius: 0;
            border: 0;
        }
    }
    .fp-sheet-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.25rem 1.5rem 1rem;
        border-bottom: 1px solid rgba(255,255,255,.06);
        flex-shrink: 0;
    }
    .fp-sheet-body {
        flex: 1;
        min-height: 0;
        overflow: auto;
        padding: 1.25rem 1.5rem 1.5rem;
    }
    .fp-btn {
        cursor: pointer;
        border: 0;
        appearance: none;
        font: inherit;
        transition: transform .12s ease, filter .12s ease, background .12s ease, border-color .12s ease;
    }
    .fp-btn:active { transform: scale(.97); }
    .fp-btn-ghost {
        background: rgba(255,255,255,.06);
        color: #c5cad3;
        border-radius: .9rem;
        padding: .7rem 1rem;
        font-weight: 800;
    }
    .fp-btn-ghost:hover { background: rgba(255,255,255,.1); color: #fff; }
    .fp-btn-primary {
        background: linear-gradient(90deg, #2db84f, #35c759 45%, #5be67a);
        color: #0f1115;
        border-radius: 1.1rem;
        min-height: 3.75rem;
        width: 100%;
        font-weight: 900;
        font-size: 1.2rem;
        box-shadow: 0 14px 36px rgba(53,199,89,.28);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
    }
    .fp-btn-primary:hover { filter: brightness(1.06); }
    .fp-tool {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        min-height: 2.75rem;
        padding: 0 .9rem;
        border-radius: .9rem;
        background: rgba(255,255,255,.07);
        color: #d7dbe2;
        font-size: .85rem;
        font-weight: 800;
        text-decoration: none;
    }
    .fp-tool:hover { background: rgba(255,255,255,.14); color: #fff; }
    .fp-tool-chuva { background: rgba(255,214,10,.16); color: #ffd60a; }
    .fp-tool-chuva.is-on { background: #ffd60a; color: #0f1115; }
    .fp-choice-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-top: 1.25rem;
    }
    @media (max-width: 640px) {
        .fp-choice-grid { grid-template-columns: 1fr; }
    }
    .fp-choice {
        min-height: 11rem;
        border-radius: 1.5rem;
        border: 2px solid rgba(255,255,255,.1);
        background: rgba(255,255,255,.03);
        color: #fff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        font-size: 1.75rem;
        font-weight: 900;
        cursor: pointer;
        text-align: center;
        padding: 1rem;
    }
    .fp-choice:hover {
        border-color: rgba(53,199,89,.7);
        background: rgba(53,199,89,.1);
    }
    .fp-choice span { font-size: .95rem; font-weight: 700; color: #9ca3af; }
    .fp-reserve-grid {
        display: grid;
        grid-template-columns: 1.05fr .95fr;
        gap: 1.5rem;
        align-items: start;
        height: 100%;
    }
    @media (max-width: 860px) {
        .fp-reserve-grid { grid-template-columns: 1fr; }
    }
    .fp-code-box {
        min-height: 5rem;
        border-radius: 1.25rem;
        border: 2px solid #35c759;
        background: #0b0d11;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.75rem;
        font-weight: 900;
        letter-spacing: .12em;
        font-variant-numeric: tabular-nums;
        box-shadow: inset 0 0 0 1px rgba(53,199,89,.15), 0 0 30px rgba(53,199,89,.12);
    }
    .fp-pad {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .75rem;
        margin-top: 1rem;
    }
    .fp-pad-key {
        min-height: 4.5rem;
        border-radius: 1.15rem;
        background: rgba(255,255,255,.06);
        border: 1px solid rgba(255,255,255,.08);
        color: #fff;
        font-size: 1.85rem;
        font-weight: 900;
        cursor: pointer;
    }
    .fp-pad-key:hover { background: rgba(255,255,255,.1); }
    .fp-pad-ok {
        background: linear-gradient(180deg, #3dd66a, #2db84f);
        color: #0f1115;
        border: 0;
        font-size: 1.15rem;
        box-shadow: 0 10px 24px rgba(53,199,89,.25);
    }
    .fp-chip {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        border-radius: 999px;
        background: rgba(255,255,255,.06);
        padding: .4rem .8rem .4rem .4rem;
        font-weight: 800;
    }
    .fp-toast {
        position: fixed;
        inset: 0;
        z-index: 50;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        background: rgba(0,0,0,.72);
        backdrop-filter: blur(8px);
    }
    .fp-toast-card {
        width: min(28rem, 100%);
        border-radius: 1.75rem;
        border: 1px solid rgba(255,255,255,.1);
        background: #1a1d23;
        padding: 2rem;
        text-align: center;
        box-shadow: 0 30px 80px rgba(0,0,0,.5);
    }
    .fp-prazo {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .6rem;
        border-radius: 1rem;
        background: rgba(255,214,10,.14);
        color: #ffd60a;
        padding: .7rem 1rem;
        font-weight: 900;
    }
    .fp-prazo.is-urgent { background: rgba(239,68,68,.2); color: #fca5a5; animation: fp-blink 1s ease-in-out infinite; }
    @keyframes fp-blink { 50% { opacity: .55; } }
</style>

    <header class="fp-top">
        <div class="flex min-w-0 items-center gap-3">
            @if (auth()->user()->tenant?->exibeLogoNoAmbiente())
                <x-marca-ambiente :tenant="auth()->user()->tenant" :full="false" size="md" />
            @endif
            <div class="min-w-0">
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-brand">{{ auth()->user()->tenant->nome }}</p>
                <h1 class="truncate text-2xl font-black tracking-tight sm:text-3xl">Agenda de hoje</h1>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2.5">
            @if ($chuva)
                <span class="inline-flex items-center gap-2 rounded-full bg-accent px-4 py-2 text-sm font-black uppercase text-canvas">
                    <x-heroicon-o-cloud class="h-5 w-5" />
                    Chuva · só quadras cobertas · simples {{ $duracoes['simples'] }} min
                </span>
            @endif

            @if ($podeSecretaria)
                <div class="flex rounded-full bg-white/5 p-1 ring-1 ring-white/10">
                    <button type="button" wire:click="usarModo('tablet')" class="fp-btn rounded-full px-4 py-2 text-sm font-black {{ $modo === 'tablet' ? 'bg-brand text-canvas' : 'text-muted hover:text-white' }}">
                        Tablet
                    </button>
                    <button type="button" wire:click="usarModo('secretaria')" class="fp-btn rounded-full px-4 py-2 text-sm font-black {{ $modo === 'secretaria' ? 'bg-brand text-canvas' : 'text-muted hover:text-white' }}">
                        Secretaria
                    </button>
                </div>
            @endif

            @if ($modo === 'secretaria')
                <button type="button"
                        wire:click="alternarChuva"
                        @if (! $chuva) wire:confirm="Ligar modo chuva? Só as quadras cobertas ficam liberadas e o simples passa a durar {{ $minutosChuva }} minutos e as duplas continuam {{ $duracoes['duplas'] }}." @endif
                        class="fp-btn fp-tool fp-tool-chuva {{ $chuva ? 'is-on' : '' }}">
                    <x-heroicon-o-cloud class="h-5 w-5" />
                    {{ $chuva ? 'Parou de chover' : 'Choveu' }}
                </button>
                <a href="{{ route('bloqueios.index') }}" class="fp-tool" title="Bloquear quadra para torneio, aula ou manutenção">
                    <x-heroicon-o-no-symbol class="h-5 w-5" />
                    <span class="hidden md:inline">Bloquear quadra</span>
                </a>
                <a href="{{ route('socios.index') }}" class="fp-tool" title="Cadastrar atleta e enviar o link da facial por WhatsApp">
                    <x-heroicon-o-camera class="h-5 w-5" />
                    <span class="hidden md:inline">Facial / Atletas</span>
                </a>
                <a href="{{ route('dashboard') }}" class="fp-tool" title="Voltar ao painel">
                    <x-heroicon-o-home class="h-5 w-5" />
                </a>
                <button type="button" @click="linha = ! linha" class="fp-btn fp-tool xl:hidden">
                    <x-heroicon-o-bell-alert class="h-5 w-5" />
                    Acontecendo
                </button>
            @endif

            <x-ajuda-rapida :modo="$modo" wire:key="ajuda-{{ $modo }}" />

            <div class="text-right" x-data="{ agora: '{{ $agora->format('H:i') }}' }"
                 x-init="setInterval(() => agora = new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }), 10000)">
                <p class="text-4xl font-black tabular-nums leading-none" x-text="agora">{{ $agora->format('H:i') }}</p>
                <p class="text-xs font-semibold text-muted">{{ $agora->translatedFormat('d M') }}</p>
            </div>
            <x-marca :full="false" size="sm" />
        </div>
    </header>

    @if ($exigirFacial && $modo === 'tablet')
        <p class="px-5 pb-2 text-sm font-semibold text-accent">Anti-fraude estilo catraca: o rosto precisa bater com o cadastro. Na secretaria basta conferir a foto.</p>
    @endif

    <div class="fp-main">
        <div class="fp-stage">
            <x-agenda-quadras :agenda="$agenda" :pode-reservar="true" :pode-gerir="$modo === 'secretaria'" />
        </div>

        @if ($modo === 'secretaria')
            <aside class="fp-aside" x-show="linha" x-cloak>
                <div class="flex shrink-0 items-center justify-between gap-2 border-b border-white/10 px-4 py-3">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-brand">Ao vivo</p>
                        <h2 class="text-lg font-black">O que está acontecendo</h2>
                    </div>
                    <button type="button" @click="linha = false" class="fp-btn fp-btn-ghost xl:hidden">Fechar</button>
                </div>
                <div class="min-h-0 flex-1 space-y-2 overflow-y-auto p-3">
                    @forelse ($atividades as $atividade)
                        @php
                            $cor = match ($atividade->tipo) {
                                'reserva_criada' => '#35c759',
                                'jogador_entrou' => '#38bdf8',
                                'tempo_extra' => '#ffd60a',
                                'quadra_liberada' => '#a78bfa',
                                'reserva_perdida', 'cadastro_expirado' => '#f87171',
                                'chuva_ligada', 'chuva_desligada', 'reserva_com_pendencia' => '#fbbf24',
                                default => '#94a3b8',
                            };
                        @endphp
                        <div wire:key="ev-{{ $atividade->id }}" class="fp-event {{ $atividade->created_at->gt(now()->subSeconds(90)) ? 'is-new' : '' }}" style="border-left-color: {{ $cor }}">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-black leading-snug">{{ $atividade->titulo }}</p>
                                <span class="shrink-0 text-xs font-bold tabular-nums text-muted">{{ $atividade->created_at->format('H:i') }}</span>
                            </div>
                            @if ($atividade->detalhe)
                                <p class="mt-0.5 text-xs leading-snug text-muted">{{ $atividade->detalhe }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="flex h-full min-h-[10rem] items-center justify-center rounded-2xl border border-dashed border-white/10 px-4 text-center text-sm text-muted">
                            Nada por enquanto. Reservas, entradas, perdas e chuva aparecem aqui.
                        </div>
                    @endforelse
                </div>
            </aside>
        @endif
    </div>

    @if ($mensagem !== '')
        <div class="fp-toast" wire:key="toast-{{ md5($mensagem) }}"
             x-data x-init="setTimeout(() => $wire.limparResultado(), {{ $erro ? 8000 : 5000 }})">
            <div class="fp-toast-card">
                <div class="{{ $erro ? 'text-red-400' : 'text-brand' }}">
                    @if ($erro)
                        <x-heroicon-o-x-circle class="mx-auto h-20 w-20" />
                    @else
                        <x-heroicon-o-check-circle class="mx-auto h-20 w-20" />
                    @endif
                </div>
                <p class="mt-5 text-3xl font-black leading-tight">{{ $mensagem }}</p>
                <button type="button" wire:click="limparResultado" class="fp-btn fp-btn-primary mt-8">
                    OK
                </button>
            </div>
        </div>
    @endif

    @if ($painelAberto && $mensagem === '')
        <div class="fp-overlay">
            <div class="fp-sheet">
                <div class="fp-sheet-head">
                    <div class="min-w-0">
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-brand">{{ $quadraNome }}</p>
                        <h2 class="mt-1 text-3xl font-black tracking-tight">
                            {{ $entrarReservaId ? 'Entrar no jogo' : 'Nova reserva' }}
                        </h2>
                        @if ($entrarReservaId)
                            <p class="mt-2 text-sm font-semibold text-muted">
                                Jogo das {{ $hora }} às {{ $horaFim }} · o horário não muda · {{ $jogoDeEntrada['vagas'] ?? 0 }} vaga(s)
                            </p>
                        @elseif ($modalidade !== '')
                            <p class="mt-2 text-sm font-semibold text-muted">
                                {{ $hora }} – {{ $horaFim }} · {{ $modalidade === 'duplas' ? 'Duplas' : 'Simples' }} · jogador {{ $passoJogador }} de {{ $totalJogadores }}
                            </p>
                        @endif
                    </div>
                    <button type="button" wire:click="fechar" class="fp-btn fp-btn-ghost shrink-0">
                        Fechar
                    </button>
                </div>

                <div class="fp-sheet-body">
                    @if ($entrarReservaId)
                        @if (! empty($jogoDeEntrada['jogadores']))
                            <div class="mb-5 flex flex-wrap items-center gap-2">
                                <span class="text-xs font-black uppercase tracking-[0.18em] text-muted">Já estão jogando</span>
                                @foreach ($jogoDeEntrada['jogadores'] as $jogador)
                                    <span class="fp-chip">
                                        @if ($jogador['foto'])
                                            <img src="{{ $jogador['foto'] }}" alt="" class="h-8 w-8 rounded-full object-cover">
                                        @else
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand text-xs font-black text-canvas">{{ $jogador['iniciais'] }}</span>
                                        @endif
                                        {{ $jogador['primeiro_nome'] }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        @include('domains.reservas.partials.identificar')
                    @elseif ($modalidade === '')
                        <div class="rounded-3xl bg-black/30 px-5 py-5 text-center ring-1 ring-white/10">
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-muted">Seu horário</p>
                            <p class="mt-2 text-5xl font-black tabular-nums text-brand">{{ $hora }} – {{ $horaFim }}</p>
                            <p class="mt-2 text-sm text-muted">Já encadeado no fim do jogo anterior da quadra.</p>
                            @if ($quadraSaibro)
                                <label class="mx-auto mt-5 flex max-w-xl items-start gap-3 rounded-2xl bg-accent/15 p-4 text-left">
                                    <input type="checkbox" wire:model.live="arrumarQuadra" class="mt-1 h-5 w-5 rounded border-line">
                                    <span>
                                        <span class="block text-lg font-black">Pedir 5 minutos para arrumar a quadra</span>
                                        <span class="mt-1 block text-sm text-muted">Só no saibro. O horário fica 5 minutos maior.</span>
                                    </span>
                                </label>
                            @endif
                        </div>
                        <p class="mt-8 text-center text-xl font-black">Como vão jogar?</p>
                        <p class="mt-1 text-center text-sm text-muted">Toque em uma opção para continuar</p>
                        <div class="fp-choice-grid">
                            <button type="button" wire:click="escolherModalidade('simples')" class="fp-btn fp-choice">
                                Simples
                                <span>2 jogadores · {{ $duracoes['simples'] }} min</span>
                            </button>
                            <button type="button" wire:click="escolherModalidade('duplas')" class="fp-btn fp-choice">
                                Duplas
                                <span>4 jogadores · {{ $duracoes['duplas'] }} min</span>
                            </button>
                        </div>
                    @else
                        @if ($jogadores !== [])
                            <div class="mb-5 flex flex-wrap items-center gap-2">
                                @foreach ($jogadores as $jogador)
                                    <span class="fp-chip">
                                        @if ($jogador['foto'])
                                            <img src="{{ $jogador['foto'] }}" alt="" class="h-8 w-8 rounded-full object-cover">
                                        @else
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand text-xs font-black text-canvas">{{ $jogador['iniciais'] }}</span>
                                        @endif
                                        {{ $jogador['primeiro_nome'] }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        @if ($prazoRestante !== null && count($jogadores) < $totalJogadores)
                            <div wire:key="prazo-{{ $prazoAte }}"
                                 x-data="{
                                    resta: {{ (int) $prazoRestante }},
                                    disparou: false,
                                    texto() { return Math.floor(this.resta / 60) + ':' + String(this.resta % 60).padStart(2, '0') },
                                 }"
                                 x-init="setInterval(() => {
                                    if (resta > 0) { resta-- }
                                    else if (! disparou) { disparou = true; $wire.expirarPrazo() }
                                 }, 1000)"
                                 class="fp-prazo mb-5"
                                 :class="resta <= 15 ? 'is-urgent' : ''">
                                <x-heroicon-o-clock class="h-6 w-6" />
                                <span>Falta o próximo atleta se cadastrar · <span class="tabular-nums" x-text="texto()"></span></span>
                            </div>
                        @endif

                        @if (count($jogadores) < $totalJogadores)
                            @include('domains.reservas.partials.identificar')
                        @else
                            <div class="mx-auto flex max-w-lg flex-col items-center py-8 text-center">
                                <x-heroicon-o-check-circle class="h-16 w-16 text-brand" />
                                <p class="mt-4 text-2xl font-black">Todos os jogadores confirmados</p>
                                <p class="mt-2 text-muted">{{ $hora }} – {{ $horaFim }} · {{ $quadraNome }}</p>
                                <button type="button" wire:click="salvar" class="fp-btn fp-btn-primary mt-8 max-w-md">
                                    <x-heroicon-o-check class="h-7 w-7" />
                                    Confirmar reserva
                                </button>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
