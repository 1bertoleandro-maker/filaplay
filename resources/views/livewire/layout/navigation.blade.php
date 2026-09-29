<?php

use App\Domains\Jogadores\Enums\UserRole;
use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect(url('/'));
    }
}; ?>

@php
    $papel = auth()->user()->role;
@endphp

<aside class="border-b border-line bg-card lg:min-h-screen lg:w-60 lg:border-b-0 lg:border-r">
    <div class="flex items-center justify-between px-6 py-5">
        <a href="{{ route('dashboard') }}" class="block min-w-0">
            <x-marca-ambiente :tenant="auth()->user()->tenant" full size="md" />
        </a>
    </div>

    <nav class="space-y-2 px-4 pb-6">
        <a href="{{ route('dashboard') }}"
           class="flex min-h-12 items-center gap-3 rounded-xl px-4 text-base font-semibold {{ request()->routeIs('dashboard') ? 'bg-brand text-canvas' : 'text-white hover:bg-canvas' }}">
            <x-heroicon-o-home class="h-6 w-6" />
            Início
        </a>

        @if (in_array($papel, [UserRole::Administrador, UserRole::Recepcao], true))
            <a href="{{ route('clube.editar') }}"
               class="flex min-h-12 items-center gap-3 rounded-xl px-4 text-base font-semibold {{ request()->routeIs('clube.editar') ? 'bg-brand text-canvas' : 'text-white hover:bg-canvas' }}">
                <x-heroicon-o-building-office-2 class="h-6 w-6" />
                Clube
            </a>
        @endif

        @if (in_array($papel, [UserRole::Administrador, UserRole::Recepcao, UserRole::Professor], true))
            <a href="{{ route('quadras.index') }}"
               class="flex min-h-12 items-center gap-3 rounded-xl px-4 text-base font-semibold {{ request()->routeIs('quadras.index') ? 'bg-brand text-canvas' : 'text-white hover:bg-canvas' }}">
                <x-heroicon-o-squares-2x2 class="h-6 w-6" />
                Quadras
            </a>
        @endif

        @if (in_array($papel, [UserRole::Administrador, UserRole::Recepcao], true))
            <a href="{{ route('socios.index') }}"
               class="flex min-h-12 items-center gap-3 rounded-xl px-4 text-base font-semibold {{ request()->routeIs('socios.index') ? 'bg-brand text-canvas' : 'text-white hover:bg-canvas' }}">
                <x-heroicon-o-users class="h-6 w-6" />
                Sócios
            </a>
            <a href="{{ route('reservas.index') }}"
               class="flex min-h-12 items-center gap-3 rounded-xl px-4 text-base font-semibold {{ request()->routeIs('reservas.index') ? 'bg-brand text-canvas' : 'text-white hover:bg-canvas' }}">
                <x-heroicon-o-calendar-days class="h-6 w-6" />
                Reservas
            </a>
            <a href="{{ route('bloqueios.index') }}"
               class="flex min-h-12 items-center gap-3 rounded-xl px-4 text-base font-semibold {{ request()->routeIs('bloqueios.index') ? 'bg-brand text-canvas' : 'text-white hover:bg-canvas' }}">
                <x-heroicon-o-lock-closed class="h-6 w-6" />
                Bloqueios
            </a>
        @endif

        @if (in_array($papel, [UserRole::Administrador, UserRole::Recepcao, UserRole::Professor], true))
            <a href="{{ route('filas.index') }}"
               class="flex min-h-12 items-center gap-3 rounded-xl px-4 text-base font-semibold {{ request()->routeIs('filas.index') ? 'bg-brand text-canvas' : 'text-white hover:bg-canvas' }}">
                <x-heroicon-o-queue-list class="h-6 w-6" />
                Fila e partidas
            </a>
            <a href="{{ route('tv.painel') }}" target="_blank" rel="noopener"
               class="flex min-h-12 items-center gap-3 rounded-xl px-4 text-base font-semibold {{ request()->routeIs('tv.painel') ? 'bg-brand text-canvas' : 'text-white hover:bg-canvas' }}">
                <x-heroicon-o-tv class="h-6 w-6" />
                Painel TV
            </a>
            <a href="{{ route('tablet.reserva') }}" target="_blank" rel="noopener"
               class="flex min-h-12 items-center gap-3 rounded-xl px-4 text-base font-semibold {{ request()->routeIs('tablet.reserva') ? 'bg-brand text-canvas' : 'text-white hover:bg-canvas' }}">
                <x-heroicon-o-device-tablet class="h-6 w-6" />
                Reserva no tablet
            </a>
        @endif

        @if (auth()->user()->super_admin)
            <p class="px-4 pt-4 text-xs font-bold uppercase tracking-[0.2em] text-muted">Super admin</p>
            <a href="{{ route('admin.clubes') }}"
               class="flex min-h-12 items-center gap-3 rounded-xl px-4 text-base font-semibold {{ request()->routeIs('admin.clubes') ? 'bg-brand text-canvas' : 'text-white hover:bg-canvas' }}">
                <x-heroicon-o-shield-check class="h-6 w-6" />
                Clubes da plataforma
            </a>
        @endif

        <a href="{{ route('profile') }}"
           class="flex min-h-12 items-center gap-3 rounded-xl px-4 text-base font-semibold {{ request()->routeIs('profile') ? 'bg-brand text-canvas' : 'text-white hover:bg-canvas' }}">
            <x-heroicon-o-user-circle class="h-6 w-6" />
            Perfil
        </a>
        <button type="button" wire:click="logout"
                class="flex min-h-12 w-full items-center gap-3 rounded-xl px-4 text-left text-base font-semibold text-white hover:bg-canvas">
            <x-heroicon-o-arrow-right-start-on-rectangle class="h-6 w-6" />
            Sair
        </button>
    </nav>

    <div class="hidden px-6 pb-6 text-sm text-muted lg:block">
        <p class="font-semibold text-white">{{ auth()->user()->tenant->nome }}</p>
        <p>{{ auth()->user()->nome }}</p>
        <p class="mt-4">Powered by GoTreino</p>
    </div>
</aside>
