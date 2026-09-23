<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>FilaPlay</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-canvas font-sans text-white antialiased">
        <main class="relative mx-auto min-h-screen max-w-6xl px-6 py-12">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(53,199,89,0.16),_transparent_40%)]"></div>

            <div class="relative z-10 flex flex-col items-center text-center">
                <x-marca full size="xl" />
                <p class="mt-8 max-w-2xl text-2xl text-muted">
                    Cadastro do clube, quadras com foto, sócios, horários, reservas e painel de TV — no mesmo sistema.
                </p>
                <a href="{{ route('login') }}"
                   class="mt-8 inline-flex min-h-16 items-center justify-center rounded-2xl bg-brand px-10 text-xl font-bold text-canvas">
                    Entrar no painel
                </a>
            </div>

            <div class="relative z-10 mt-14 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <article class="rounded-3xl border border-line bg-card p-6 text-left">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand">Clube</p>
                    <h2 class="mt-2 text-2xl font-bold">Cadastro do clube</h2>
                    <p class="mt-2 text-lg text-muted">Logo, endereço, contato e regras de horário da semana.</p>
                </article>
                <article class="rounded-3xl border border-line bg-card p-6 text-left">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand">Quadras</p>
                    <h2 class="mt-2 text-2xl font-bold">Quadras com imagem</h2>
                    <p class="mt-2 text-lg text-muted">Foto, piso, cobertura e status de cada pista.</p>
                </article>
                <article class="rounded-3xl border border-line bg-card p-6 text-left">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand">Pessoas</p>
                    <h2 class="mt-2 text-2xl font-bold">Sócios e clientes</h2>
                    <p class="mt-2 text-lg text-muted">Cadastro com foto de perfil e reconhecimento facial.</p>
                </article>
                <article class="rounded-3xl border border-line bg-card p-6 text-left">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand">Horários</p>
                    <h2 class="mt-2 text-2xl font-bold">Grade de funcionamento</h2>
                    <p class="mt-2 text-lg text-muted">Abre, fecha ou fecha o dia — as reservas respeitam essa grade.</p>
                </article>
                <article class="rounded-3xl border border-line bg-card p-6 text-left">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-accent">TV</p>
                    <h2 class="mt-2 text-2xl font-bold">Painel das quadras</h2>
                    <p class="mt-2 text-lg text-muted">Tela grande para a TV: quem está jogando e o que vem a seguir.</p>
                </article>
                <article class="rounded-3xl border border-line bg-card p-6 text-left">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand">Reservas</p>
                    <h2 class="mt-2 text-2xl font-bold">Secretaria ou facial</h2>
                    <p class="mt-2 text-lg text-muted">A secretaria confirma na hora, ou o sócio valida pelo rosto.</p>
                </article>
            </div>
        </main>
    </body>
</html>
