<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'FILAPLAY') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-canvas font-sans text-white antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
            <a href="{{ route('login') }}" class="text-center">
                <x-marca full size="lg" class="mx-auto justify-center" />
            </a>

            <div class="mt-8 w-full max-w-md rounded-3xl border border-line bg-card px-6 py-8 shadow-2xl sm:px-8">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
