<?php

declare(strict_types=1);

use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Models\User;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

test('usuario bloqueado nao entra no sistema', function () {
    $user = User::factory()->bloqueado()->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password');

    $component->call('login');

    $component->assertHasErrors('form.email');
    $this->assertGuest();
});

test('jogador nao acessa area exclusiva do administrador', function () {
    Route::middleware(['web', 'auth', 'role:administrador'])->get('/area-admin-teste', fn () => 'ok');

    $jogador = User::factory()->create(['role' => UserRole::Jogador]);

    $this->actingAs($jogador)
        ->get('/area-admin-teste')
        ->assertForbidden();
});
