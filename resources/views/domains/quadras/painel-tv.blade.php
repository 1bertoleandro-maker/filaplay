<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="12">
    <title>TV · {{ config('app.name', 'FILAPLAY') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-screen overflow-hidden bg-canvas font-sans text-white antialiased">
<div class="flex h-screen flex-col overflow-hidden bg-canvas text-white">
    <header class="flex shrink-0 items-center justify-between gap-4 px-5 py-3">
        <div class="flex min-w-0 items-center gap-4">
            @if ($clube->exibeLogoNoAmbiente())
                <img src="{{ $clube->logoUrl }}" alt="{{ $clube->nome }}" class="h-14 w-14 shrink-0 rounded-2xl bg-white object-contain p-1 ring-2 ring-brand/50">
            @else
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand text-2xl font-black text-canvas">
                    {{ mb_strtoupper(mb_substr($clube->nome, 0, 1)) }}
                </span>
            @endif
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-brand">Ao vivo</p>
                <h1 class="truncate text-2xl font-extrabold">{{ $clube->nome }}</h1>
            </div>
            @if ($clube->modo_chuva)
                <span class="flex items-center gap-2 rounded-full bg-accent px-4 py-1.5 text-sm font-black uppercase text-canvas">
                    <x-heroicon-o-cloud class="h-5 w-5" /> Modo chuva
                </span>
            @endif
        </div>
        <div class="flex items-center gap-5">
            <div class="text-right">
                <p class="text-4xl font-black tabular-nums leading-none">{{ $agora->format('H:i') }}</p>
                <p class="text-sm text-muted">{{ $agora->translatedFormat('d M') }}</p>
            </div>
            <x-marca full size="sm" class="opacity-70" />
        </div>
    </header>

    <section class="grid min-h-0 flex-1 gap-3 px-3 pb-3" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
        @foreach ($cards as $card)
            @php
                $quadra = $card['quadra'];
                $ocupada = $card['ocupada'];
                $partida = $card['partida'];
                $reserva = $card['reserva_atual'];
                $bloqueio = $card['bloqueio'];
            @endphp
            <article data-quadra-id="{{ $quadra->id }}" data-ocupada="{{ $ocupada ? '1' : '0' }}" class="flex min-h-0 min-w-0 flex-col overflow-hidden rounded-3xl border {{ $ocupada ? 'border-accent/60' : 'border-line' }} bg-card">
                <div class="relative h-[26vh] max-h-48 min-h-32 w-full shrink-0">
                    <img src="{{ $quadra->fotoUrl }}" alt="{{ $quadra->nome }}" class="h-full w-full object-cover">
                    <span class="absolute left-3 top-3 rounded-full px-3 py-1 text-[11px] font-extrabold uppercase tracking-wider {{ $bloqueio ? 'bg-red-500 text-white' : ($ocupada ? 'bg-accent text-canvas' : 'bg-brand text-canvas') }}">
                        {{ $bloqueio ? $bloqueio->tipo->label() : ($ocupada ? 'Em jogo' : 'Livre') }}
                    </span>
                    @if ($partida)
                        <span class="absolute right-3 top-3 rounded-full bg-canvas/80 px-3 py-1 text-lg font-black tabular-nums text-white" data-cronometro data-fim="{{ $card['fim_partida']->toIso8601String() }}">--:--</span>
                    @endif
                </div>

                <div class="shrink-0 px-3 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted">{{ $quadra->nome }}</p>
                    <h2 class="truncate text-2xl font-black">{{ $quadra->apelido ?: $quadra->nome }}</h2>

                    @if ($bloqueio)
                        <p class="mt-2 text-sm font-semibold text-red-400">{{ $bloqueio->motivo }}</p>
                    @elseif ($partida)
                        <div class="mt-2 flex items-center gap-2">
                            @foreach ($partida->jogadores->take(4) as $jogador)
                                <x-avatar :user="$jogador" size="sm" />
                            @endforeach
                            @if ($partida->jogadores->count() > 4)
                                <span class="text-sm text-muted">+{{ $partida->jogadores->count() - 4 }}</span>
                            @endif
                        </div>
                        <p class="mt-1 truncate text-sm font-bold text-accent">{{ $partida->modalidade->label() }}</p>
                    @elseif ($reserva)
                        <div class="mt-2 flex items-center gap-3">
                            <x-avatar :user="$reserva->user" size="md" />
                            <div class="min-w-0">
                                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-accent">Reservado</p>
                                <p class="truncate text-lg font-extrabold">{{ $reserva->user->nome }}</p>
                                <p class="text-sm tabular-nums text-muted">{{ $reserva->inicio->format('H:i') }} – {{ $reserva->fim->format('H:i') }}</p>
                            </div>
                        </div>
                    @else
                        <p class="mt-2 text-sm font-semibold text-brand">Quadra livre</p>
                    @endif
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto border-t border-line px-3 py-2">
                    @if ($card['chamados']->isNotEmpty())
                        <div class="mb-2 rounded-xl bg-accent/15 px-2 py-1.5">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-accent">Chamando</p>
                            @foreach ($card['chamados'] as $chamado)
                                <p class="truncate text-sm font-bold">{{ $chamado->user->nome }}</p>
                            @endforeach
                        </div>
                    @endif

                    <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.18em] text-muted">Próximos na fila</p>
                    <ul class="space-y-1.5">
                        @forelse ($card['aguardando'] as $fila)
                            <li class="flex items-center gap-2 rounded-xl bg-canvas/60 px-2 py-1.5">
                                <p class="w-12 shrink-0 text-sm font-black tabular-nums">
                                    {{ $fila->horario_estimado?->format('H:i') ?? '--:--' }}
                                </p>
                                <x-avatar :user="$fila->user" size="sm" />
                                <p class="min-w-0 truncate text-sm font-semibold">{{ $fila->user->nome }}</p>
                            </li>
                        @empty
                            <li class="px-2 py-2 text-center text-sm text-muted">Fila vazia</li>
                        @endforelse
                    </ul>
                </div>
            </article>
        @endforeach
    </section>
</div>

<script>
    function tocarBipLiberado() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            [880, 1180].forEach(function (freq, i) {
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = freq;
                gain.gain.setValueAtTime(0.2, ctx.currentTime + i * 0.18);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.18 + 0.35);
                osc.connect(gain).connect(ctx.destination);
                osc.start(ctx.currentTime + i * 0.18);
                osc.stop(ctx.currentTime + i * 0.18 + 0.35);
            });
        } catch (e) {}
    }

    (function () {
        // Cronômetro das partidas em andamento, atualizado a cada segundo sem depender do refresh da página.
        function tick() {
            document.querySelectorAll('[data-cronometro]').forEach(function (el) {
                var fim = new Date(el.getAttribute('data-fim')).getTime();
                var restante = Math.max(0, Math.floor((fim - Date.now()) / 1000));
                var m = String(Math.floor(restante / 60)).padStart(2, '0');
                var s = String(restante % 60).padStart(2, '0');
                el.textContent = (fim < Date.now() ? '+' : '') + m + ':' + s;
            });
        }
        tick();
        setInterval(tick, 1000);

        // Alerta sonoro quando uma quadra que estava ocupada é liberada,
        // comparando com o estado salvo antes do último refresh automático.
        try {
            var estadoAtual = {};
            document.querySelectorAll('[data-quadra-id]').forEach(function (el) {
                estadoAtual[el.getAttribute('data-quadra-id')] = el.getAttribute('data-ocupada') === '1';
            });
            var estadoAnterior = JSON.parse(localStorage.getItem('filaplay_tv_estado') || '{}');
            var liberouAlguma = Object.keys(estadoAtual).some(function (id) {
                return estadoAnterior[id] === true && estadoAtual[id] === false;
            });
            if (liberouAlguma) {
                tocarBipLiberado();
            }
            localStorage.setItem('filaplay_tv_estado', JSON.stringify(estadoAtual));
        } catch (e) {}
    })();
</script>
</body>
</html>
