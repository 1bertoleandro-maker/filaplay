<?php

declare(strict_types=1);

use App\Domains\Clube\Models\Tenant;
use App\Domains\Jogadores\Actions\SalvarSocio;
use App\Domains\Jogadores\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Route;

function doisClubes(): array
{
    $casa = Tenant::factory()->create(['nome' => 'Clube Casa']);
    $outro = Tenant::factory()->create(['nome' => 'Clube Cliente']);

    $master = User::factory()->administrador()->create(['tenant_id' => $casa->id, 'super_admin' => true]);

    User::factory()->create(['tenant_id' => $casa->id, 'nome' => 'Socio da Casa', 'matricula' => 'C1']);
    User::factory()->create(['tenant_id' => $outro->id, 'nome' => 'Socio do Cliente', 'matricula' => 'X1']);

    return [$casa, $outro, $master];
}

test('super admin sem clube escolhido cai na lista de clubes', function () {
    [, , $master] = doisClubes();

    $this->actingAs($master)->get(route('dashboard'))->assertRedirect(route('admin.clubes'));
});

test('super admin entra em outro clube e ve os socios dele como administrador', function () {
    [, $outro, $master] = doisClubes();

    $this->actingAs($master)
        ->post(route('admin.clubes.entrar', $outro->id))
        ->assertRedirect(route('dashboard'));

    $this->get(route('socios.index'))
        ->assertOk()
        ->assertSee('Socio do Cliente')
        ->assertDontSee('Socio da Casa')
        ->assertSee('Clube Cliente');

    // O clube de origem do super admin não muda no banco.
    expect($master->fresh()->tenant_id)->not->toBe($outro->id);
});

test('socio cadastrado pelo super admin dentro do clube escolhido vai para esse clube', function () {
    [$casa, $outro, $master] = doisClubes();

    $this->actingAs($master)->post(route('admin.clubes.entrar', $outro->id));

    // Durante a requisição o usuário "é" do clube escolhido: é esse tenant_id que as ações de cadastro usam.
    Route::middleware(['web', 'auth'])->post('/__cadastro-teste', function () {
        $novo = app(SalvarSocio::class)->handle(auth()->user(), [
            'nome' => 'Novo do Cliente',
            'matricula' => 'N1',
            'email' => 'novo.cliente@exemplo.com',
            'role' => 'jogador',
        ]);

        return response()->json(['tenant_id' => $novo->tenant_id]);
    });

    $this->post('/__cadastro-teste')->assertOk()->assertJson(['tenant_id' => $outro->id]);

    expect(User::withoutGlobalScopes()->where('email', 'novo.cliente@exemplo.com')->value('tenant_id'))->toBe($outro->id)
        ->and($master->fresh()->tenant_id)->toBe($casa->id);
});

test('super admin sai do clube e volta para a lista de clubes', function () {
    [, $outro, $master] = doisClubes();

    $this->actingAs($master)->post(route('admin.clubes.entrar', $outro->id));
    $this->post(route('admin.clubes.sair'))->assertRedirect(route('admin.clubes'));
    $this->get(route('dashboard'))->assertRedirect(route('admin.clubes'));
});

test('administrador comum nao entra em outro clube', function () {
    [$casa, $outro] = doisClubes();
    $admin = User::factory()->administrador()->create(['tenant_id' => $casa->id, 'super_admin' => false]);

    $this->actingAs($admin)->post(route('admin.clubes.entrar', $outro->id))->assertForbidden();

    app(TenantContext::class)->forget();
});
