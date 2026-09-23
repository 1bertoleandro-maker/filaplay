@props([
    'tenant' => null,
    'size' => 'md',
    'full' => true,
])

@php
    $tenant ??= auth()->user()?->tenant;
    $usarLogoCliente = $tenant?->exibeLogoNoAmbiente() === true;

    $quadro = match ($size) {
        'sm' => 'h-8 w-8',
        'lg' => 'h-14 w-14',
        'xl' => 'h-16 w-16',
        default => 'h-10 w-10',
    };
    $texto = match ($size) {
        'sm' => 'text-base',
        'lg' => 'text-2xl',
        'xl' => 'text-3xl',
        default => 'text-xl',
    };
@endphp

@if ($usarLogoCliente)
    <span {{ $attributes->class('inline-flex min-w-0 items-center gap-2.5') }}>
        <img src="{{ $tenant->logoUrl }}" alt="{{ $tenant->nome }}"
             class="{{ $quadro }} shrink-0 rounded-xl bg-white object-contain p-0.5 ring-1 ring-line">
        @if ($full)
            <span class="{{ $texto }} truncate font-extrabold leading-tight text-white">{{ $tenant->nome }}</span>
        @endif
    </span>
@else
    <x-marca :full="$full" :size="$size" {{ $attributes }} />
@endif
