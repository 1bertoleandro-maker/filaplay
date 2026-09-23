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
            <a href="{{ route('tv.painel') }}" class="rounded-2xl border border-line bg-card p-6 hover:border-accent">
                <p class="text-2xl font-bold">Painel da TV</p>
                <p class="mt-2 text-muted">Ocupação ao vivo nas quadras</p>
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
