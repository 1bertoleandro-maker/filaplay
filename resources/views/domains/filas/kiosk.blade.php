<div
    x-data="{
        tela: 'home',
        matricula: '',
        modalidade: 'duplas',
        digitar(d) { if (this.matricula.length < 10) this.matricula += d },
        apagar() { this.matricula = this.matricula.slice(0, -1) },
        reiniciar() { this.tela = 'home'; this.matricula = ''; this.modalidade = 'duplas'; $wire.limpar() },
        confirmarPresenca() { $wire.confirmarPresenca(this.matricula).then(() => this.tela = 'resultado') },
        entrarNaFila() { $wire.entrarNaFila(this.matricula, this.modalidade).then(() => this.tela = 'resultado') },
        sairDaFila() { $wire.sairDaFila(this.matricula).then(() => this.tela = 'resultado') },
    }"
    class="flex h-screen w-full flex-col bg-canvas p-6 text-white"
>
    <header class="flex shrink-0 items-center justify-between pb-4">
        <div class="flex min-w-0 items-center gap-3">
            @if ($quadra->tenant?->exibeLogoNoAmbiente())
                <x-marca-ambiente :tenant="$quadra->tenant" :full="false" size="lg" />
            @endif
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.3em] text-brand">{{ $quadra->tenant->nome }}</p>
                <h1 class="text-3xl font-black">{{ $quadra->apelido ?: $quadra->nome }}</h1>
            </div>
        </div>
        <x-marca :full="false" size="sm" />
    </header>

    {{-- TELA INICIAL: 3 botões gigantes --}}
    <template x-if="tela === 'home'">
        <div class="grid min-h-0 flex-1 grid-rows-3 gap-5">
            <button type="button" @click="tela = 'entrar_modalidade'"
                class="flex flex-col items-center justify-center gap-3 rounded-3xl bg-brand text-canvas active:scale-[0.98]">
                <x-heroicon-o-user-plus class="h-16 w-16" />
                <span class="text-4xl font-black">ENTRAR NA FILA</span>
            </button>
            <button type="button" @click="tela = 'confirmar_matricula'"
                class="flex flex-col items-center justify-center gap-3 rounded-3xl bg-accent text-canvas active:scale-[0.98]">
                <x-heroicon-o-finger-print class="h-16 w-16" />
                <span class="text-4xl font-black">CONFIRMAR PRESENÇA</span>
            </button>
            <button type="button" @click="tela = 'sair_matricula'"
                class="flex flex-col items-center justify-center gap-3 rounded-3xl border-2 border-line bg-card text-white active:scale-[0.98]">
                <x-heroicon-o-arrow-left-start-on-rectangle class="h-16 w-16" />
                <span class="text-4xl font-black">SAIR DA FILA</span>
            </button>
        </div>
    </template>

    {{-- ESCOLHA DE MODALIDADE (parte do fluxo "entrar na fila") --}}
    <template x-if="tela === 'entrar_modalidade'">
        <div class="flex min-h-0 flex-1 flex-col gap-5">
            <p class="text-center text-3xl font-bold">Qual modalidade?</p>
            <div class="grid min-h-0 flex-1 grid-cols-2 gap-5">
                @foreach ($modalidades as $m)
                    <button type="button" @click="modalidade = '{{ $m->value }}'; tela = 'entrar_matricula'"
                        class="flex flex-col items-center justify-center gap-2 rounded-3xl border-2 border-line bg-card active:scale-[0.98]">
                        <span class="text-4xl font-black">{{ $m->label() }}</span>
                        <span class="text-xl text-muted">{{ $m->jogadores() ?? '2 a 8' }} jogadores</span>
                    </button>
                @endforeach
            </div>
            <button type="button" @click="tela = 'home'" class="min-h-16 rounded-2xl text-2xl font-bold text-muted">Voltar</button>
        </div>
    </template>

    {{-- TECLADO NUMÉRICO (reutilizado para confirmar / entrar / sair) --}}
    <template x-if="['confirmar_matricula', 'entrar_matricula', 'sair_matricula'].includes(tela)">
        <div class="flex min-h-0 flex-1 flex-col items-center gap-4">
            <p class="text-3xl font-bold">Digite sua matrícula</p>
            <div class="min-h-20 w-full max-w-md rounded-2xl border-2 border-brand bg-card text-center text-5xl font-black tabular-nums leading-[5rem]" x-text="matricula || '—'"></div>

            <div class="grid w-full max-w-md grid-cols-3 gap-3">
                <template x-for="n in [1,2,3,4,5,6,7,8,9]" :key="n">
                    <button type="button" @click="digitar(n)" class="min-h-20 rounded-2xl bg-card text-4xl font-black active:scale-95" x-text="n"></button>
                </template>
                <button type="button" @click="apagar()" class="min-h-20 rounded-2xl bg-card text-2xl font-black active:scale-95">⌫</button>
                <button type="button" @click="digitar(0)" class="min-h-20 rounded-2xl bg-card text-4xl font-black active:scale-95">0</button>
                <button type="button" @click="tela = 'home'; matricula = ''" class="min-h-20 rounded-2xl bg-card text-xl font-bold text-muted active:scale-95">Cancelar</button>
            </div>

            <button type="button"
                x-show="tela === 'confirmar_matricula'" @click="confirmarPresenca()"
                class="min-h-20 w-full max-w-md rounded-2xl bg-accent text-3xl font-black text-canvas">Confirmar presença</button>
            <button type="button"
                x-show="tela === 'entrar_matricula'" @click="entrarNaFila()"
                class="min-h-20 w-full max-w-md rounded-2xl bg-brand text-3xl font-black text-canvas">Entrar na fila</button>
            <button type="button"
                x-show="tela === 'sair_matricula'" @click="sairDaFila()"
                class="min-h-20 w-full max-w-md rounded-2xl bg-red-500 text-3xl font-black text-white">Sair da fila</button>
        </div>
    </template>

    {{-- RESULTADO --}}
    <template x-if="tela === 'resultado'">
        <div class="flex min-h-0 flex-1 flex-col items-center justify-center gap-8 text-center">
            <div class="{{ $erro ? 'text-red-400' : 'text-brand' }}">
                @if ($erro)
                    <x-heroicon-o-x-circle class="mx-auto h-24 w-24" />
                @else
                    <x-heroicon-o-check-circle class="mx-auto h-24 w-24" />
                @endif
            </div>
            <p class="max-w-xl text-4xl font-black">{{ $mensagem }}</p>
            <button type="button" @click="reiniciar()" class="min-h-16 rounded-2xl bg-white px-10 text-2xl font-bold text-canvas">OK</button>
        </div>
    </template>
</div>
