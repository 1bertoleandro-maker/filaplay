@php
    use App\Domains\Jogadores\Enums\UserRole;
    $papel = auth()->user()->role;
@endphp

<div class="mx-auto max-w-5xl">
    <div class="flex flex-wrap items-center gap-4">
        @if (auth()->user()->tenant?->exibeLogoNoAmbiente())
            <img src="{{ auth()->user()->tenant->logoUrl }}" alt="{{ auth()->user()->tenant->nome }}"
                 class="h-16 w-16 rounded-2xl bg-white object-contain p-1 ring-1 ring-line">
        @endif
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">{{ auth()->user()->tenant->nome }}</p>
            <h1 class="mt-2 text-4xl font-bold leading-tight">Olá, {{ auth()->user()->nome }}</h1>
            <p class="mt-2 text-xl text-muted">{{ auth()->user()->role->label() }}</p>
        </div>
    </div>

    @if (in_array($papel, [UserRole::Administrador, UserRole::Recepcao], true))
        <section class="mt-8 grid gap-4 lg:grid-cols-3">
            <a href="{{ route('tablet.reserva', ['modo' => 'secretaria']) }}"
               class="group relative flex min-h-44 flex-col justify-between overflow-hidden rounded-3xl bg-gradient-to-br from-brand via-brand to-emerald-400 p-7 text-canvas shadow-xl shadow-brand/25 transition hover:brightness-105 lg:col-span-2">
                <span class="absolute -right-6 -top-6 h-40 w-40 rounded-full bg-white/15 blur-2xl"></span>
                <span class="relative flex items-center gap-3 text-sm font-black uppercase tracking-[0.22em]">
                    <x-heroicon-o-calendar-days class="h-6 w-6" />
                    Recepção
                </span>
                <span class="relative">
                    <span class="block text-5xl font-black leading-none tracking-tight">Reservar quadra</span>
                    <span class="mt-3 block text-lg font-bold opacity-80">Busque o atleta pelo nome ou código, confira a foto e confirme. Também: +minutos, quadra livre, chuva e bloqueios.</span>
                </span>
            </a>
            <div class="grid gap-4">
                <a href="{{ route('tablet.reserva', ['modo' => 'tablet']) }}" target="_blank" rel="noopener"
                   class="flex flex-1 flex-col justify-center rounded-3xl border-2 border-brand/50 bg-brand/10 p-6 transition hover:border-brand hover:bg-brand/15">
                    <span class="flex items-center gap-2 text-sm font-black uppercase tracking-[0.2em] text-brand">
                        <x-heroicon-o-device-tablet class="h-5 w-5" /> Tablet do atleta
                    </span>
                    <span class="mt-2 block text-2xl font-black">Abrir em tela cheia</span>
                    <span class="mt-1 block text-sm text-muted">O próprio atleta se identifica pelo rosto ou pelo código e reserva.</span>
                </a>
                <a href="{{ route('tv.painel') }}" target="_blank" rel="noopener"
                   class="flex flex-1 flex-col justify-center rounded-3xl border border-line bg-card p-6 transition hover:border-accent">
                    <span class="flex items-center gap-2 text-sm font-black uppercase tracking-[0.2em] text-accent">
                        <x-heroicon-o-tv class="h-5 w-5" /> TV do clube
                    </span>
                    <span class="mt-2 block text-2xl font-black">Abrir painel</span>
                    <span class="mt-1 block text-sm text-muted">Alertas grandes de nova reserva e de entrada.</span>
                </a>
            </div>
        </section>
    @endif

    @php
        $passosIniciais = [
            ['Cadastrar o horário de funcionamento', $horarioOk, route('clube.editar')],
            ['Cadastrar as quadras', $quadras > 0, route('quadras.index')],
            ['Cadastrar os atletas', $jogadores > 0, route('socios.index')],
            ['Cadastrar a facial de pelo menos um atleta', $comFacial > 0, route('socios.index')],
        ];
        $faltam = collect($passosIniciais)->where(1, false)->count();
    @endphp

    @if (in_array($papel, [UserRole::Administrador, UserRole::Recepcao], true) && $faltam > 0)
        <section class="mt-8 rounded-3xl border border-accent/40 bg-accent/10 p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.2em] text-accent">Primeiros passos</p>
                    <p class="mt-1 text-xl font-bold">Faltam {{ $faltam }} {{ $faltam === 1 ? 'passo' : 'passos' }} para deixar tudo pronto</p>
                </div>
                <a href="{{ route('ajuda') }}" class="rounded-xl border border-accent/50 px-4 py-2 font-bold text-accent hover:bg-accent/10">Ver ajuda completa</a>
            </div>
            <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                @foreach ($passosIniciais as [$texto, $feito, $link])
                    <li>
                        <a href="{{ $link }}" class="flex items-center gap-3 rounded-2xl bg-canvas/60 px-4 py-3 hover:bg-canvas">
                            @if ($feito)
                                <x-heroicon-s-check-circle class="h-7 w-7 shrink-0 text-brand" />
                                <span class="font-semibold text-muted line-through">{{ $texto }}</span>
                            @else
                                <span class="h-7 w-7 shrink-0 rounded-full border-2 border-accent"></span>
                                <span class="font-bold">{{ $texto }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="mt-8 grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <article class="rounded-2xl border border-line bg-card p-6">
            <p class="text-lg text-muted">Ocupação agora</p>
            <p class="mt-2 text-5xl font-bold text-accent">{{ $ocupacaoPercentual }}%</p>
        </article>
        <article class="rounded-2xl border border-line bg-card p-6">
            <p class="text-lg text-muted">Espera média</p>
            <p class="mt-2 text-5xl font-bold">{{ $tempoMedioEspera }}<span class="text-2xl text-muted">min</span></p>
        </article>
        <article class="rounded-2xl border border-line bg-card p-6">
            <p class="text-lg text-muted">No-shows hoje</p>
            <p class="mt-2 text-5xl font-bold text-red-400">{{ $noShowsHoje }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-card p-6">
            <p class="text-lg text-muted">Reservas hoje</p>
            <p class="mt-2 text-5xl font-bold">{{ $reservasHoje }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-card p-6">
            <p class="text-lg text-muted">Sócios</p>
            <p class="mt-2 text-5xl font-bold">{{ $socios }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-card p-6">
            <p class="text-lg text-muted">Quadras</p>
            <p class="mt-2 text-5xl font-bold">{{ $quadras }}</p>
            <p class="text-sm text-muted">{{ $cobertas }} cobertas</p>
        </article>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2">
        @if (in_array($papel, [UserRole::Administrador, UserRole::Recepcao], true))
            <a href="{{ route('clube.editar') }}" class="rounded-2xl border border-line bg-card p-6 hover:border-brand">
                <p class="text-2xl font-bold">Cadastrar empresa / clube</p>
                <p class="mt-2 text-muted">Nome, logo e horário da semana</p>
            </a>
        @endif
        @if (in_array($papel, [UserRole::Administrador, UserRole::Recepcao, UserRole::Professor], true))
            <a href="{{ route('quadras.index') }}" class="rounded-2xl border border-line bg-card p-6 hover:border-brand">
                <p class="text-2xl font-bold">Cadastrar quadras</p>
                <p class="mt-2 text-muted">Foto, apelido e status para a TV</p>
            </a>
            <a href="{{ route('filas.index') }}" class="rounded-2xl border border-line bg-card p-6 hover:border-accent">
                <p class="text-2xl font-bold">Fila e partidas</p>
                <p class="mt-2 text-muted">Chamar próximos, tempo extra, liberar quadra</p>
            </a>
            <a href="{{ route('tv.painel') }}" target="_blank" rel="noopener" class="rounded-2xl border border-line bg-card p-6 hover:border-accent">
                <p class="text-2xl font-bold">Painel da TV</p>
                <p class="mt-2 text-muted">Ocupação ao vivo nas quadras</p>
            </a>
            <a href="{{ route('tablet.reserva') }}" target="_blank" rel="noopener" class="rounded-2xl border border-line bg-card p-6 hover:border-brand">
                <p class="text-2xl font-bold">Reserva no tablet</p>
                <p class="mt-2 text-muted">Agenda presencial das quadras</p>
            </a>
        @endif
        @if (in_array($papel, [UserRole::Administrador, UserRole::Recepcao], true))
            <a href="{{ route('socios.index') }}" class="rounded-2xl border border-line bg-card p-6 hover:border-brand">
                <p class="text-2xl font-bold">Sócios</p>
                <p class="mt-2 text-muted">Clientes e cadastro facial</p>
            </a>
            <a href="{{ route('reservas.index') }}" class="rounded-2xl border border-line bg-card p-6 hover:border-brand">
                <p class="text-2xl font-bold">Reservas</p>
                <p class="mt-2 text-muted">Secretaria ou reconhecimento facial</p>
            </a>
            <a href="{{ route('bloqueios.index') }}" class="rounded-2xl border border-line bg-card p-6 hover:border-brand">
                <p class="text-2xl font-bold">Bloqueios</p>
                <p class="mt-2 text-muted">Torneios, aulas e manutenção</p>
            </a>
        @endif
    </div>

    @if ($quadras > 0)
        <div class="mt-8 rounded-2xl border border-line bg-card p-6">
            <p class="text-2xl font-bold">Kiosk das quadras (Tablet)</p>
            <p class="mt-2 text-muted">Abra em cada tablet fixado na quadra para os sócios entrarem na fila, confirmarem presença ou saírem.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach (\App\Domains\Quadras\Models\Quadra::query()->orderBy('ordem_exibicao')->get() as $quadra)
                    <a href="{{ route('kiosk', $quadra) }}" target="_blank" class="rounded-xl border border-line px-4 py-2 font-semibold hover:border-brand">{{ $quadra->apelido ?: $quadra->nome }} ↗</a>
                @endforeach
            </div>
        </div>
    @endif
</div>
