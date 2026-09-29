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
