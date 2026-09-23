@props([
    'user' => null,
    'nome' => '',
    'src' => null,
    'size' => 'md',
])

@php
    $rotulo = $nome !== '' ? $nome : (string) ($user?->nome ?? '');
    $foto = $src ?? $user?->avatarUrl;
    $dim = match ($size) {
        'sm' => 'h-10 w-10 text-sm',
        'lg' => 'h-20 w-20 text-2xl',
        'xl' => 'h-28 w-28 text-4xl',
        default => 'h-14 w-14 text-lg',
    };
    $inicial = $rotulo !== '' ? mb_strtoupper(mb_substr($rotulo, 0, 1)) : '?';
@endphp

@if ($foto)
    <img src="{{ $foto }}" alt="{{ $rotulo }}" {{ $attributes->class($dim.' rounded-full object-cover ring-2 ring-white/20') }}>
@else
    <span {{ $attributes->class($dim.' inline-flex items-center justify-center rounded-full bg-brand font-bold text-canvas') }}>{{ $inicial }}</span>
@endif
