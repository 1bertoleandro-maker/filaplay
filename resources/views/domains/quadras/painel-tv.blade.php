<!DOCTYPE html>
<html lang="pt-BR" class="{{ \App\Support\Tema::classeHtml() }} h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="12">
    <title>TV · {{ config('app.name', 'FILAPLAY') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:500,600,700,800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background:
                radial-gradient(ellipse 80% 50% at 50% -10%, rgba(53,199,89,.16), transparent 55%),
                radial-gradient(ellipse 50% 40% at 100% 100%, rgba(255,214,10,.06), transparent 45%),
                #0f1115;
        }
    </style>
</head>
<body class="h-screen overflow-hidden font-sans text-white antialiased">
<div class="flex h-screen flex-col overflow-hidden">
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
                    <x-heroicon-o-cloud class="h-5 w-5" /> Modo chuva
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
        <x-agenda-quadras :agenda="$agenda" :pode-reservar="false" />
    </div>
</div>
</body>
</html>
