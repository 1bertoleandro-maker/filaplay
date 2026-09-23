@props([
    'size' => 'md',
    'full' => false,
])

@php
    $dimensoes = match ($size) {
        'sm' => ['badge' => 'h-7 w-9', 'texto' => 'text-base'],
        'lg' => ['badge' => 'h-11 w-16', 'texto' => 'text-2xl'],
        'xl' => ['badge' => 'h-14 w-20', 'texto' => 'text-3xl'],
        default => ['badge' => 'h-9 w-12', 'texto' => 'text-2xl'],
    };
    $clipId = 'marca-bola-'.preg_replace('/[^a-z0-9\-]/i', '', (string) $size);
@endphp

{{-- Cores no próprio SVG para o símbolo não sumir sem rebuild do CSS. --}}
<span {{ $attributes->class('inline-flex items-center gap-2') }}>
    <svg viewBox="0 0 48 40" class="{{ $dimensoes['badge'] }} shrink-0" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <circle cx="18" cy="20" r="14" fill="#35c759" />
        <g clip-path="url(#{{ $clipId }})">
            <path d="M10.5 8.2c5.4 3 5.4 17.6 0 20.6" fill="none" stroke="#ffffff" stroke-width="1.7" stroke-linecap="round" />
            <path d="M25.5 8.2c-5.4 3-5.4 17.6 0 20.6" fill="none" stroke="#ffffff" stroke-width="1.7" stroke-linecap="round" />
        </g>
        <clipPath id="{{ $clipId }}">
            <circle cx="18" cy="20" r="14" />
        </clipPath>
        <circle cx="35.2" cy="25.5" r="2.6" fill="#ffd60a" />
        <circle cx="40.4" cy="21.4" r="1.8" fill="#ffd60a" />
        <circle cx="44.4" cy="17.6" r="1.15" fill="#ffd60a" />
    </svg>

    @if ($full)
        <span class="{{ $dimensoes['texto'] }} font-extrabold leading-none tracking-tight">
            <span class="text-brand">Fila</span><span class="text-accent">Play</span>
        </span>
    @endif
</span>
