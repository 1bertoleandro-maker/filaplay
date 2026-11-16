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
    $item = 'flex min-h-12 items-center gap-3 rounded-xl px-4 text-base font-semibold lg:justify-center lg:px-2 xl:justify-start xl:px-4';
    $idle = 'text-white hover:bg-canvas';
    $ativo = 'bg-brand text-canvas';
@endphp

<aside
    x-data="{ aberto: false }"
    class="border-b border-line bg-card lg:sticky lg:top-0 lg:flex lg:h-screen lg:w-16 lg:shrink-0 lg:flex-col lg:overflow-y-auto lg:border-b-0 lg:border-r xl:w-60"
>
    <div class="flex items-center justify-between gap-3 px-4 py-4 lg:justify-center lg:px-2 xl:justify-between xl:px-5">
        <a href="{{ route('dashboard') }}" class="block min-w-0 lg:hidden xl:block">
            <x-marca-ambiente :tenant="auth()->user()->tenant" full size="md" />
        </a>
        <a href="{{ route('dashboard') }}" class="hidden lg:block xl:hidden" title="Início">
            <x-marca-ambiente :tenant="auth()->user()->tenant" :full="false" size="sm" />
        </a>
        <button
            type="button"
            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-white hover:bg-canvas lg:hidden"
            x-on:click="aberto = !aberto"
            x-bind:aria-expanded="aberto.toString()"
            aria-label="Abrir ou fechar o menu"
        >
            <span x-show="!aberto"><x-heroicon-o-bars-3 class="h-6 w-6" /></span>
            <span x-show="aberto" x-cloak><x-heroicon-o-x-mark class="h-6 w-6" /></span>
        </button>
    </div>

    <nav class="space-y-1 px-3 pb-4 max-lg:hidden" x-bind:class="aberto && '!block'">
        <a href="{{ route('dashboard') }}" title="Início"
           class="{{ $item }} {{ request()->routeIs('dashboard') ? $ativo : $idle }}">
            <x-heroicon-o-home class="h-6 w-6 shrink-0" />
            <span class="truncate lg:hidden xl:inline">Início</span>
        </a>

        @if (in_array($papel, [UserRole::Administrador, UserRole::Recepcao], true))
            <a href="{{ route('clube.editar') }}" title="Clube"
               class="{{ $item }} {{ request()->routeIs('clube.editar') ? $ativo : $idle }}">
                <x-heroicon-o-building-office-2 class="h-6 w-6 shrink-0" />
                <span class="truncate lg:hidden xl:inline">Clube</span>
            </a>
        @endif

        @if (in_array($papel, [UserRole::Administrador, UserRole::Recepcao, UserRole::Professor], true))
            <a href="{{ route('quadras.index') }}" title="Quadras"
               class="{{ $item }} {{ request()->routeIs('quadras.index') ? $ativo : $idle }}">
                <x-heroicon-o-squares-2x2 class="h-6 w-6 shrink-0" />
                <span class="truncate lg:hidden xl:inline">Quadras</span>
            </a>
        @endif

        @if (in_array($papel, [UserRole::Administrador, UserRole::Recepcao], true))
            <a href="{{ route('socios.index') }}" title="Sócios"
               class="{{ $item }} {{ request()->routeIs('socios.index') ? $ativo : $idle }}">
                <x-heroicon-o-users class="h-6 w-6 shrink-0" />
                <span class="truncate lg:hidden xl:inline">Sócios</span>
            </a>
            @if ($papel === UserRole::Administrador)
                <a href="{{ route('equipe.index') }}" title="Equipe"
                   class="{{ $item }} {{ request()->routeIs('equipe.index') ? $ativo : $idle }}">
                    <x-heroicon-o-identification class="h-6 w-6 shrink-0" />
                    <span class="truncate lg:hidden xl:inline">Equipe</span>
                </a>
            @endif
            <a href="{{ route('reservas.index') }}" title="Reservas"
               class="{{ $item }} {{ request()->routeIs('reservas.index') ? $ativo : $idle }}">
                <x-heroicon-o-calendar-days class="h-6 w-6 shrink-0" />
                <span class="truncate lg:hidden xl:inline">Reservas</span>
            </a>
            <a href="{{ route('bloqueios.index') }}" title="Bloqueios"
               class="{{ $item }} {{ request()->routeIs('bloqueios.index') ? $ativo : $idle }}">
                <x-heroicon-o-lock-closed class="h-6 w-6 shrink-0" />
                <span class="truncate lg:hidden xl:inline">Bloqueios</span>
            </a>
        @endif

        @if (in_array($papel, [UserRole::Administrador, UserRole::Recepcao, UserRole::Professor], true))
            <a href="{{ route('filas.index') }}" title="Fila e partidas"
               class="{{ $item }} {{ request()->routeIs('filas.index') ? $ativo : $idle }}">
                <x-heroicon-o-queue-list class="h-6 w-6 shrink-0" />
                <span class="truncate lg:hidden xl:inline">Fila e partidas</span>
            </a>
            <a href="{{ route('tv.painel') }}" target="_blank" rel="noopener" title="Painel TV"
               class="{{ $item }} {{ request()->routeIs('tv.painel') ? $ativo : $idle }}">
                <x-heroicon-o-tv class="h-6 w-6 shrink-0" />
                <span class="truncate lg:hidden xl:inline">Painel TV</span>
            </a>
            <a href="{{ route('tablet.reserva') }}" target="_blank" rel="noopener" title="Reserva no tablet"
               class="{{ $item }} {{ request()->routeIs('tablet.reserva') ? $ativo : $idle }}">
                <x-heroicon-o-device-tablet class="h-6 w-6 shrink-0" />
                <span class="truncate lg:hidden xl:inline">Reserva no tablet</span>
            </a>
        @endif

        @if (auth()->user()->super_admin)
            <p class="px-4 pt-4 text-xs font-bold uppercase tracking-[0.2em] text-muted lg:hidden xl:block">Super admin</p>
            <a href="{{ route('admin.clubes') }}" title="Clubes da plataforma"
               class="{{ $item }} {{ request()->routeIs('admin.clubes') ? $ativo : $idle }}">
                <x-heroicon-o-shield-check class="h-6 w-6 shrink-0" />
                <span class="truncate lg:hidden xl:inline">Clubes da plataforma</span>
            </a>
        @endif

        <a href="{{ route('ajuda') }}" title="Ajuda"
           class="{{ $item }} {{ request()->routeIs('ajuda') ? $ativo : $idle }}">
            <x-heroicon-o-question-mark-circle class="h-6 w-6 shrink-0" />
            <span class="truncate lg:hidden xl:inline">Ajuda</span>
        </a>
        <a href="{{ route('profile') }}" title="Perfil"
           class="{{ $item }} {{ request()->routeIs('profile') ? $ativo : $idle }}">
            <x-heroicon-o-user-circle class="h-6 w-6 shrink-0" />
            <span class="truncate lg:hidden xl:inline">Perfil</span>
        </a>
        <button type="button" wire:click="logout" title="Sair"
                class="{{ $item }} w-full text-left {{ $idle }}">
            <x-heroicon-o-arrow-right-start-on-rectangle class="h-6 w-6 shrink-0" />
            <span class="truncate lg:hidden xl:inline">Sair</span>
        </button>
    </nav>

    <div class="mt-auto hidden px-5 pb-6 text-sm text-muted xl:block">
        <p class="font-semibold text-white">{{ auth()->user()->tenant->nome }}</p>
        <p>{{ auth()->user()->nome }}</p>
        <p class="mt-4">Powered by GoTreino</p>
    </div>
</aside>
