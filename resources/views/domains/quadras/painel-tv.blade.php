<div
    class="flex h-screen flex-col overflow-hidden font-sans text-white"
    style="background:
        radial-gradient(ellipse 80% 50% at 50% -10%, rgba(53,199,89,.16), transparent 55%),
        radial-gradient(ellipse 50% 40% at 100% 100%, rgba(255,214,10,.06), transparent 45%),
        #0f1115;"
    wire:poll.4s
    x-data="tvAlertas"
>
    <style>
        @keyframes tv-borda { 0%, 100% { box-shadow: 0 0 0 0 rgba(255,255,255,.0), inset 0 0 0 14px var(--tv-cor); } 50% { box-shadow: 0 0 0 0 rgba(255,255,255,.0), inset 0 0 0 40px var(--tv-cor); } }
        @keyframes tv-pisca { 0%, 100% { opacity: 1; } 50% { opacity: .55; } }
        @keyframes tv-entra { 0% { transform: scale(.94); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
        .tv-alerta { animation: tv-borda 1.4s ease-in-out infinite; }
        .tv-alerta-titulo { animation: tv-pisca 1.1s ease-in-out infinite; }
        .tv-alerta-card { animation: tv-entra .35s ease-out 1; }
    </style>

    {{-- Dados lidos pelo Alpine a cada ciclo (o atributo é atualizado pelo Livewire). --}}
    <div id="tv-dados" class="hidden"
         data-chamadas="{{ json_encode($chamadas, JSON_UNESCAPED_UNICODE) }}"
         data-novas="{{ json_encode($novas, JSON_UNESCAPED_UNICODE) }}"
         data-ultimo="{{ $ultimoId }}"></div>

    <header class="flex shrink-0 items-center justify-between gap-4 px-5 py-4">
        <div class="flex min-w-0 items-center gap-4">
            @if ($clube->exibeLogoNoAmbiente())
                <img src="{{ $clube->logoUrl }}" alt="{{ $clube->nome }}" class="h-16 w-16 shrink-0 rounded-2xl bg-white object-contain p-1.5 shadow-lg shadow-brand/20 ring-2 ring-brand/40">
            @else
                <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-brand text-3xl font-black text-canvas shadow-lg shadow-brand/30">
                    {{ mb_strtoupper(mb_substr($clube->nome, 0, 1)) }}
                </span>
            @endif
            <div class="min-w-0">
                <p class="flex items-center gap-2 text-[11px] font-black uppercase tracking-[0.28em] text-brand">
                    <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-brand"></span>
                    Ao vivo
                </p>
                <h1 class="truncate text-3xl font-black tracking-tight">{{ $clube->nome }}</h1>
            </div>
            @if ($clube->modo_chuva)
                <span class="flex items-center gap-2 rounded-full bg-accent px-4 py-1.5 text-sm font-black uppercase text-canvas">
                    <x-heroicon-o-cloud class="h-5 w-5" /> Modo chuva · só quadras cobertas
                </span>
            @endif
        </div>
        <div class="flex items-center gap-5">
            <div class="text-right" x-data="{ agora: '{{ $agora->format('H:i') }}' }"
                 x-init="setInterval(() => agora = new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }), 10000)">
                <p class="text-5xl font-black tabular-nums leading-none tracking-tight" x-text="agora">{{ $agora->format('H:i') }}</p>
                <p class="mt-1 text-sm font-semibold text-muted">{{ $agora->translatedFormat('d M') }}</p>
            </div>
            <x-marca full size="sm" class="opacity-80" />
        </div>
    </header>

    <div class="min-h-0 flex-1 px-4 pb-4">
        <x-agenda-quadras :agenda="$agenda" :pode-reservar="false" :tv="true" />
    </div>

    {{-- Ativar som: navegadores só liberam áudio depois de um toque. --}}
    <button type="button"
            x-show="! somPronto" x-cloak
            @click="ativarSom()"
            class="fixed bottom-5 right-5 z-40 flex items-center gap-3 rounded-full bg-accent px-6 py-4 text-lg font-black text-canvas shadow-2xl"
            style="animation: tv-pisca 1.6s ease-in-out infinite;">
        <x-heroicon-o-speaker-wave class="h-7 w-7" />
        Toque para ativar o som dos avisos
    </button>

    {{-- Alerta estilo senha de hospital: tela inteira, letras enormes. --}}
    <div x-show="atual" x-cloak
         class="tv-alerta fixed inset-0 z-50 flex items-center justify-center p-10"
         :style="`--tv-cor: ${atual ? atual.cor : '#35c759'}; background: #05070a;`">
        <template x-if="atual">
            <div class="tv-alerta-card w-full max-w-[110rem] text-center">
                <p class="tv-alerta-titulo font-black uppercase leading-none tracking-[0.12em]"
                   style="font-size: clamp(2.5rem, 5.5vw, 6rem);"
                   :style="`color: ${atual.cor}`"
                   x-text="atual.titulo"></p>

                <div class="mt-10 grid gap-8" :class="atual.cards.length > 1 ? 'grid-cols-2' : 'grid-cols-1'">
                    <template x-for="card in atual.cards" :key="card.quadra + card.horario">
                        <article class="rounded-[3rem] px-8 py-10"
                                 :style="`background: ${atual.cor}; color: #05070a;`">
                            <p class="font-black leading-none tracking-tight"
                               :style="`font-size: clamp(3.5rem, ${atual.cards.length > 1 ? '8vw' : '12vw'}, 14rem);`"
                               x-text="card.quadra"></p>
                            <p class="mt-6 font-black tabular-nums leading-none"
                               :style="`font-size: clamp(2.5rem, ${atual.cards.length > 1 ? '6vw' : '8vw'}, 10rem);`"
                               x-text="card.horario"></p>
                            <p class="mt-6 font-extrabold leading-tight"
                               style="font-size: clamp(1.5rem, 3.2vw, 4rem);"
                               x-text="card.nomes"></p>
                            <p class="mt-2 font-bold opacity-80"
                               style="font-size: clamp(1.1rem, 2vw, 2.4rem);"
                               x-text="card.extra"></p>
                        </article>
                    </template>
                </div>
            </div>
        </template>
    </div>
</div>
