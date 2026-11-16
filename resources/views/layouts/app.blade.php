<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ \App\Support\Tema::classeHtml() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'FILAPLAY') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-canvas font-sans text-ink antialiased">
        <div class="min-h-screen lg:flex">
            <livewire:layout.navigation />

            <div class="min-w-0 flex-1">
                @if (auth()->user()?->super_admin)
                    @php $naPlataforma = request()->routeIs('admin.clubes'); @endphp
                    <div class="sticky top-0 z-30 flex flex-wrap items-center justify-between gap-2 border-b border-amber-400/40 bg-amber-400 px-4 py-2 text-sm font-semibold text-[#0f1115] sm:px-8">
                        <p>
                            @if (session()->has(\App\Http\Middleware\SetTenant::CLUBE_ATIVO))
                                Super admin · você está no clube <strong>{{ auth()->user()->tenant?->nome }}</strong>.
                                O que você cadastrar aqui (sócios, fotos, quadras, planilhas) vai para este clube.
                            @elseif ($naPlataforma)
                                Super admin · escolha um clube para entrar e ver os cadastros dele como administrador.
                            @else
                                Super admin · clube: <strong>{{ auth()->user()->tenant?->nome }}</strong>
                            @endif
                        </p>
                        @if (! $naPlataforma)
                            <a href="{{ route('admin.clubes') }}" class="rounded-lg bg-[#0f1115] px-3 py-1 text-amber-300">Trocar de clube</a>
                        @endif
                    </div>
                @endif

                @php $clubeDoUsuario = auth()->user()?->super_admin ? null : auth()->user()?->tenant; @endphp
                @if ($clubeDoUsuario?->emTeste() && auth()->user()->role === \App\Domains\Jogadores\Enums\UserRole::Administrador)
                    @php $dias = $clubeDoUsuario->diasRestantes(); @endphp
                    <div @class([
                        'flex flex-wrap items-center justify-between gap-2 border-b px-4 py-2 text-sm font-semibold sm:px-8',
                        'border-brand/30 bg-brand/10 text-brand' => $dias > 7,
                        'border-amber-400/40 bg-amber-400/15 text-amber-600' => $dias <= 7,
                    ])>
                        <p>
                            Teste grátis: {{ $dias === 0 ? 'termina hoje' : "faltam {$dias} dia(s)" }}.
                            Depois fale com {{ \App\Support\Contato::rotulo() }} para contratar.
                        </p>
                        <a href="{{ \App\Support\Contato::url() }}" target="_blank" rel="noopener" class="rounded-lg bg-brand px-3 py-1 text-canvas">Contratar</a>
                    </div>
                @endif

                @if (isset($header))
                    <header class="border-b border-line bg-card">
                        <div class="mx-auto max-w-5xl px-4 py-6 sm:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <main class="px-4 py-8 sm:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
