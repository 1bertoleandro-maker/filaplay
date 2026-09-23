<div
    x-data="{
        tela: 'quadra',
        quadraId: null,
        quadraNome: '',
        hora: '',
        codigo1: '',
        codigo2: '',
        alvo: 'codigo1',
        digitar(d) { if (this[this.alvo].length < 10) this[this.alvo] += d },
        apagar() { this[this.alvo] = this[this.alvo].slice(0, -1) },
        reiniciar() { tela = 'quadra'; quadraId = null; hora = ''; codigo1 = ''; codigo2 = ''; alvo = 'codigo1'; $wire.limpar() },
        confirmar() { $wire.reservar(quadraId, hora, codigo1, codigo2).then(() => tela = 'resultado') },
    }"
    class="flex h-screen w-full flex-col bg-canvas p-6 text-white"
>
    <header class="flex shrink-0 items-center justify-between pb-4">
        <div class="flex min-w-0 items-center gap-3">
            @if (auth()->user()->tenant?->exibeLogoNoAmbiente())
                <x-marca-ambiente :tenant="auth()->user()->tenant" :full="false" size="lg" />
            @endif
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.3em] text-brand">{{ auth()->user()->tenant->nome }}</p>
                <h1 class="text-3xl font-black">Reservar quadra</h1>
            </div>
        </div>
        <x-marca :full="false" size="sm" />
    </header>

    {{-- 1. ESCOLHER QUADRA --}}
    <template x-if="tela === 'quadra'">
        <div class="flex min-h-0 flex-1 flex-col gap-4">
            <p class="text-center text-2xl font-bold">Qual quadra?</p>
            <div class="grid min-h-0 flex-1 grid-cols-2 gap-4">
                @foreach ($quadras as $quadra)
                    <button type="button"
                        @click="quadraId = {{ $quadra->id }}; quadraNome = '{{ $quadra->apelido ?: $quadra->nome }}'; tela = 'hora'"
                        class="flex flex-col items-center justify-center gap-2 rounded-3xl border-2 border-line bg-card active:scale-[0.98]">
                        <span class="text-3xl font-black">{{ $quadra->apelido ?: $quadra->nome }}</span>
                        <span class="text-lg text-muted">{{ $quadra->tipo_piso?->label() }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </template>

    {{-- 2. ESCOLHER HORÁRIO DE HOJE --}}
    <template x-if="tela === 'hora'">
        <div class="flex min-h-0 flex-1 flex-col gap-4">
            <p class="text-center text-2xl font-bold" x-text="'Horário na ' + quadraNome"></p>
            <div class="grid min-h-0 flex-1 grid-cols-3 gap-4 overflow-y-auto">
                @forelse ($slots as $slot)
                    <button type="button" @click="hora = '{{ $slot }}'; tela = 'codigo1'"
                        class="flex items-center justify-center rounded-2xl border-2 border-line bg-card text-3xl font-black tabular-nums active:scale-95">
                        {{ $slot }}
                    </button>
                @empty
                    <p class="col-span-3 text-center text-xl text-muted">Sem horários disponíveis hoje.</p>
                @endforelse
            </div>
            <button type="button" @click="tela = 'quadra'" class="min-h-16 rounded-2xl text-2xl font-bold text-muted">Voltar</button>
        </div>
    </template>

    {{-- 3. SEU CÓDIGO --}}
    <template x-if="tela === 'codigo1'">
        <div class="flex min-h-0 flex-1 flex-col items-center gap-4">
            <p class="text-3xl font-bold">Digite o seu código</p>
            <div class="min-h-20 w-full max-w-md rounded-2xl border-2 border-brand bg-card text-center text-5xl font-black tabular-nums leading-[5rem]" x-text="codigo1 || '—'"></div>
            <div class="grid w-full max-w-md grid-cols-3 gap-3">
                <template x-for="n in [1,2,3,4,5,6,7,8,9]" :key="n">
                    <button type="button" @click="alvo = 'codigo1'; digitar(n)" class="min-h-20 rounded-2xl bg-card text-4xl font-black active:scale-95" x-text="n"></button>
                </template>
                <button type="button" @click="alvo = 'codigo1'; apagar()" class="min-h-20 rounded-2xl bg-card text-2xl font-black active:scale-95">⌫</button>
                <button type="button" @click="alvo = 'codigo1'; digitar(0)" class="min-h-20 rounded-2xl bg-card text-4xl font-black active:scale-95">0</button>
                <button type="button" @click="tela = 'hora'" class="min-h-20 rounded-2xl bg-card text-xl font-bold text-muted active:scale-95">Voltar</button>
            </div>
            <button type="button" @click="tela = 'codigo2'" class="min-h-20 w-full max-w-md rounded-2xl bg-brand text-3xl font-black text-canvas">Próximo</button>
        </div>
    </template>

    {{-- 4. CÓDIGO DO COLEGA (opcional) --}}
    <template x-if="tela === 'codigo2'">
        <div class="flex min-h-0 flex-1 flex-col items-center gap-4">
            <p class="text-3xl font-bold">Código do seu colega</p>
            <p class="text-lg text-muted">(se estiver jogando só, pode pular)</p>
            <div class="min-h-20 w-full max-w-md rounded-2xl border-2 border-brand bg-card text-center text-5xl font-black tabular-nums leading-[5rem]" x-text="codigo2 || '—'"></div>
            <div class="grid w-full max-w-md grid-cols-3 gap-3">
                <template x-for="n in [1,2,3,4,5,6,7,8,9]" :key="n">
                    <button type="button" @click="alvo = 'codigo2'; digitar(n)" class="min-h-20 rounded-2xl bg-card text-4xl font-black active:scale-95" x-text="n"></button>
                </template>
                <button type="button" @click="alvo = 'codigo2'; apagar()" class="min-h-20 rounded-2xl bg-card text-2xl font-black active:scale-95">⌫</button>
                <button type="button" @click="alvo = 'codigo2'; digitar(0)" class="min-h-20 rounded-2xl bg-card text-4xl font-black active:scale-95">0</button>
                <button type="button" @click="codigo2 = ''; confirmar()" class="min-h-20 rounded-2xl bg-card text-xl font-bold text-muted active:scale-95">Pular</button>
            </div>
            <button type="button" @click="confirmar()" class="min-h-20 w-full max-w-md rounded-2xl bg-brand text-3xl font-black text-canvas">Salvar reserva</button>
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
