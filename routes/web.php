<?php

use App\Domains\Clube\Livewire\CadastrarClube;
use App\Domains\Clube\Livewire\ClubesPendentes;
use App\Domains\Clube\Livewire\EditarClube;
use App\Domains\Clube\Livewire\Painel;
use App\Domains\Filas\Livewire\Kiosk;
use App\Domains\Filas\Livewire\ListaFilas;
use App\Domains\Jogadores\Http\SociosPlanilhaController;
use App\Domains\Jogadores\Livewire\CadastrarFacial;
use App\Domains\Jogadores\Livewire\ConfirmarCadastro;
use App\Domains\Jogadores\Livewire\ListaSocios;
use App\Domains\Quadras\Http\PainelTvController;
use App\Domains\Quadras\Livewire\ListaBloqueios;
use App\Domains\Quadras\Livewire\ListaQuadras;
use App\Domains\Reservas\Livewire\ListaReservas;
use App\Domains\Reservas\Livewire\ReservaTablet;
use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware('guest')->group(function (): void {
    Route::get('cadastrar-clube', CadastrarClube::class)->name('clube.cadastrar');
    Route::get('confirmar-cadastro/{token}', ConfirmarCadastro::class)->name('socios.confirmar');
    Route::get('reconhecimento-facial/{token}', CadastrarFacial::class)->name('socios.facial');

    Route::get('auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function (): void {
    Route::get('dashboard', Painel::class)->name('dashboard');
    Route::view('profile', 'profile')->name('profile');

    Route::get('admin/clubes', ClubesPendentes::class)
        ->middleware('superadmin')
        ->name('admin.clubes');

    Route::get('clube', EditarClube::class)
        ->middleware('role:administrador,recepcao')
        ->name('clube.editar');

    Route::get('quadras', ListaQuadras::class)
        ->middleware('role:administrador,recepcao,professor')
        ->name('quadras.index');

    Route::get('socios', ListaSocios::class)
        ->middleware('role:administrador,recepcao')
        ->name('socios.index');

    Route::get('socios/planilha-modelo', [SociosPlanilhaController::class, 'modelo'])
        ->middleware('role:administrador,recepcao')
        ->name('socios.planilha.modelo');

    Route::get('socios/planilha-exportar', [SociosPlanilhaController::class, 'exportar'])
        ->middleware('role:administrador,recepcao')
        ->name('socios.planilha.exportar');

    Route::get('reservas', ListaReservas::class)
        ->middleware('role:administrador,recepcao')
        ->name('reservas.index');

    Route::get('bloqueios', ListaBloqueios::class)
        ->middleware('role:administrador,recepcao')
        ->name('bloqueios.index');

    Route::get('filas', ListaFilas::class)
        ->middleware('role:administrador,recepcao,professor')
        ->name('filas.index');

    Route::get('tv', PainelTvController::class)
        ->middleware('role:administrador,recepcao,professor')
        ->name('tv.painel');

    Route::get('kiosk/{quadra}', Kiosk::class)
        ->middleware('role:administrador,recepcao,professor')
        ->name('kiosk');

    Route::get('tablet/reserva', ReservaTablet::class)
        ->middleware('role:administrador,recepcao,professor')
        ->name('tablet.reserva');
});

require __DIR__.'/auth.php';
