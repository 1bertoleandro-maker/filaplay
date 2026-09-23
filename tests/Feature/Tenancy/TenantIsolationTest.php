<?php

declare(strict_types=1);

use App\Domains\Clube\Models\Tenant;
use App\Domains\Jogadores\Models\User;
use App\Domains\Quadras\Models\Quadra;
use App\Support\Tenancy\TenantContext;

test('cada clube enxerga somente as proprias quadras', function () {
    $arena = Tenant::factory()->create(['nome' => 'Arena Isolada']);
    $praia = Tenant::factory()->create(['nome' => 'Praia Isolada']);

    Quadra::factory()->create([
        'tenant_id' => $arena->id,
        'nome' => 'Quadra Arena',
    ]);

    Quadra::factory()->create([
        'tenant_id' => $praia->id,
        'nome' => 'Quadra Praia',
    ]);

    $usuario = User::factory()->create([
        'tenant_id' => $arena->id,
        'nome' => 'Jogador Arena',
    ]);

    $this->actingAs($usuario)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Arena Isolada')
        ->assertDontSee('Praia Isolada');

    app(TenantContext::class)->set($arena->id);

    expect(Quadra::query()->pluck('nome')->all())->toBe(['Quadra Arena']);
});

test('administrador nao atualiza o clube de outro tenant', function () {
    $arena = Tenant::factory()->create();
    $praia = Tenant::factory()->create();
    $admin = User::factory()->administrador()->create(['tenant_id' => $arena->id]);

    expect($admin->can('update', $arena))->toBeTrue()
        ->and($admin->can('update', $praia))->toBeFalse();
});
