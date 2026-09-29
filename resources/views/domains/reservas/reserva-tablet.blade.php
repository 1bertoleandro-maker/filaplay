<div
    class="fp-shell"
    @if (! $painelAberto && $mensagem === '') wire:poll.12s @endif
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
        padding: 1rem 1.25rem;
        flex-shrink: 0;
    }
    .fp-stage {
        flex: 1;
        min-height: 0;
        padding: 0 1rem 1rem;
        display: flex;
        flex-direction: column;
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

        <div class="flex items-center gap-3">
            @if ($podeSecretaria)
                <div class="hidden rounded-full bg-white/5 p-1 ring-1 ring-white/10 sm:flex">
                    <button type="button" wire:click="usarModo('tablet')" class="fp-btn rounded-full px-4 py-2 text-sm font-black {{ $modo === 'tablet' ? 'bg-brand text-canvas' : 'text-muted hover:text-white' }}">
                        Tablet
                    </button>
                    <button type="button" wire:click="usarModo('secretaria')" class="fp-btn rounded-full px-4 py-2 text-sm font-black {{ $modo === 'secretaria' ? 'bg-brand text-canvas' : 'text-muted hover:text-white' }}">
                        Secretaria
                    </button>
                </div>
            @endif
            <div class="text-right" x-data="{ agora: '{{ $agora->format('H:i') }}' }"
                 x-init="setInterval(() => agora = new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }), 10000)">
                <p class="text-4xl font-black tabular-nums leading-none" x-text="agora">{{ $agora->format('H:i') }}</p>
                <p class="text-xs font-semibold text-muted">{{ $agora->translatedFormat('d M') }}</p>
            </div>
            <x-marca :full="false" size="sm" />
        </div>
    </header>

    @if ($exigirFacial && $modo === 'tablet')
        <p class="px-5 pb-2 text-sm font-semibold text-accent">Anti-fraude estilo catraca: digite o código, olhe para a câmera e libera sozinho. Na secretaria basta conferir a foto.</p>
    @endif

    <div class="fp-stage">
        <x-agenda-quadras :agenda="$agenda" :pode-reservar="true" />
    </div>

    @if ($mensagem !== '')
        <div class="fp-toast">
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
                        <h2 class="mt-1 text-3xl font-black tracking-tight">Nova reserva</h2>
                        @if ($modalidade !== '')
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
                    @if ($modalidade === '')
                        <div class="rounded-3xl bg-black/30 px-5 py-5 text-center ring-1 ring-white/10">
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-muted">Horário automático</p>
                            <p class="mt-2 text-5xl font-black tabular-nums text-brand">{{ $hora }} – {{ $horaFim }}</p>
                            <p class="mt-2 text-sm text-muted">Arredonda a chegada e encadeia no próximo livre da quadra.</p>
                        </div>
                        <p class="mt-8 text-center text-xl font-black">Como vão jogar?</p>
                        <p class="mt-1 text-center text-sm text-muted">Toque em uma opção para continuar</p>
                        <div class="fp-choice-grid">
                            <button type="button" wire:click="escolherModalidade('simples')" class="fp-btn fp-choice">
                                Simples
                                <span>2 jogadores</span>
                            </button>
                            <button type="button" wire:click="escolherModalidade('duplas')" class="fp-btn fp-choice">
                                Duplas
                                <span>4 jogadores</span>
                            </button>
                        </div>
                    @else
                        @if ($jogadores !== [])
                            <div class="mb-5 flex flex-wrap gap-2">
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

                        @if (count($jogadores) < $totalJogadores)
                            @if ($modo === 'secretaria')
                                <label class="block">
                                    <span class="text-lg font-bold text-muted">Código ou nome do sócio cadastrado</span>
                                    <div class="mt-2 flex gap-2">
                                        <input type="text" wire:model="codigo" wire:keydown.enter.prevent="identificarPorCodigo"
                                               class="w-full rounded-2xl border-line bg-canvas text-lg" placeholder="Código">
                                        <button type="button" wire:click="identificarPorCodigo" class="fp-btn rounded-2xl bg-brand px-5 font-black text-canvas">OK</button>
                                    </div>
                                </label>
                                <input type="text" wire:model.live.debounce.300ms="buscaNome"
                                       class="mt-3 w-full rounded-2xl border-line bg-canvas text-lg" placeholder="Buscar pelo nome">
                                @if ($busca)
                                    <div class="mt-3 space-y-2">
                                        @foreach ($busca as $socio)
                                            <button type="button" wire:click="escolherSocio({{ $socio['id'] }})"
                                                    class="fp-btn flex w-full items-center gap-3 rounded-2xl bg-white/5 px-3 py-3 text-left ring-1 ring-white/10 hover:bg-brand/10">
                                                @if ($socio['foto'])
                                                    <img src="{{ $socio['foto'] }}" alt="" class="h-12 w-12 rounded-full object-cover">
                                                @else
                                                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-brand font-black text-canvas">{{ $socio['iniciais'] }}</span>
                                                @endif
                                                <span>
                                                    <span class="block font-bold">{{ $socio['nome'] }}</span>
                                                    <span class="text-sm text-muted">{{ $socio['matricula'] }}</span>
                                                </span>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            @else
                                <div class="fp-reserve-grid"
                                     x-data="{
                                        codigo: @entangle('codigo'),
                                        digitar(n) { if (this.codigo.length < 10) this.codigo += String(n) },
                                        apagar() { this.codigo = this.codigo.slice(0, -1) },
                                     }">
                                    <div>
                                        <p class="text-lg font-black">1. Digite o código do sócio</p>
                                        <p class="mt-1 text-sm text-muted">Somente sócio cadastrado · use o teclado e toque em OK</p>
                                        <div class="fp-code-box mt-4" x-text="codigo || '—'"></div>

                                        @error('codigo') <p class="mt-3 text-red-400">{{ $message }}</p> @enderror
                                        @error('buscaNome') <p class="mt-3 text-red-400">{{ $message }}</p> @enderror

                                        @if ($identificado !== [])
                                            <div class="mt-5 rounded-3xl bg-brand/10 p-5 text-center ring-1 ring-brand/40">
                                                @if ($identificado['foto'])
                                                    <img src="{{ $identificado['foto'] }}" alt="{{ $identificado['nome'] }}" class="mx-auto h-20 w-20 rounded-full object-cover ring-4 ring-brand/40">
                                                @else
                                                    <span class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-brand text-2xl font-black text-canvas">{{ $identificado['iniciais'] }}</span>
                                                @endif
                                                <p class="mt-3 text-2xl font-black">{{ $identificado['nome'] }}</p>

                                                @if ($exigirFacial)
                                                    <p class="mt-1 text-sm text-accent font-semibold">Olhe para a câmera — libera sozinho</p>
                                                    @php
                                                        $urlFace = $identificado['face'] ?? $identificado['foto'] ?? null;
                                                    @endphp
                                                    @if ($urlFace)
                                                        <div class="mt-4 text-left">
                                                            <x-reconhecimento-catraca
                                                                :referencia-url="$urlFace"
                                                                :nome="$identificado['primeiro_nome']"
                                                                wire:key="catraca-{{ $identificado['id'] }}-{{ $passoJogador }}"
                                                            />
                                                            @error('foto_facial') <p class="mt-2 text-center text-red-400">{{ $message }}</p> @enderror
                                                        </div>
                                                    @else
                                                        <p class="mt-3 text-red-400">Este sócio não tem foto facial cadastrada.</p>
                                                    @endif
                                                @else
                                                    <p class="text-sm text-muted">É essa pessoa?</p>
                                                    <button type="button" wire:click="confirmarJogador" class="fp-btn fp-btn-primary mt-4">
                                                        <x-heroicon-o-check class="h-6 w-6" />
                                                        Sim, continuar
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    <div>
                                        <div class="fp-pad">
                                            @foreach ([1,2,3,4,5,6,7,8,9] as $n)
                                                <button type="button" class="fp-btn fp-pad-key" @click="digitar({{ $n }})">{{ $n }}</button>
                                            @endforeach
                                            <button type="button" class="fp-btn fp-pad-key" @click="apagar()">⌫</button>
                                            <button type="button" class="fp-btn fp-pad-key" @click="digitar(0)">0</button>
                                            <button type="button" class="fp-btn fp-pad-key fp-pad-ok" wire:click="identificarPorCodigo">OK</button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($modo === 'secretaria' && $identificado !== [])
                                <div class="mt-6 rounded-3xl bg-brand/10 p-5 text-center ring-1 ring-brand/40">
                                    @if ($identificado['foto'])
                                        <img src="{{ $identificado['foto'] }}" alt="{{ $identificado['nome'] }}" class="mx-auto h-32 w-32 rounded-full object-cover ring-4 ring-brand/40">
                                    @else
                                        <span class="mx-auto flex h-32 w-32 items-center justify-center rounded-full bg-brand text-4xl font-black text-canvas">{{ $identificado['iniciais'] }}</span>
                                    @endif
                                    <p class="mt-3 text-2xl font-black">{{ $identificado['nome'] }}</p>
                                    <p class="text-sm text-muted">Confira a foto e confirme — na secretaria não precisa de facial.</p>
                                    <button type="button" wire:click="confirmarJogador" class="fp-btn fp-btn-primary mt-4">
                                        <x-heroicon-o-check class="h-6 w-6" />
                                        Sim, continuar
                                    </button>
                                </div>
                            @endif
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
