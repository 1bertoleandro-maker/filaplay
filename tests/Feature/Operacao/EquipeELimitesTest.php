<?php

declare(strict_types=1);

use App\Domains\Clube\Livewire\ClubesPendentes;
use App\Domains\Clube\Models\Tenant;
use App\Domains\Jogadores\Actions\SalvarSocio;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Enums\UserStatus;
use App\Domains\Jogadores\Livewire\ListaEquipe;
use App\Domains\Jogadores\Livewire\ListaSocios;
use App\Domains\Jogadores\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function clubeComAdmin(array $clube = []): array
{
    $tenant = Tenant::factory()->create($clube);
    app(TenantContext::class)->set($tenant->id);
    $admin = User::factory()->administrador()->create(['tenant_id' => $tenant->id]);

    return [$tenant, $admin];
}

test('administrador cadastra a secretaria na tela de equipe e ela recebe convite', function () {
    [$tenant, $admin] = clubeComAdmin();

    Livewire::actingAs($admin)
        ->test(ListaEquipe::class)
        ->call('novo')
        ->set('nome', 'Maria Secretaria')
        ->set('email', 'maria@clube.com.br')
        ->set('telefone', '11988887777')
        ->set('role', 'recepcao')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertSee('Maria Secretaria');

    $maria = User::query()->where('email', 'maria@clube.com.br')->first();

    expect($maria->tenant_id)->toBe($tenant->id)
        ->and($maria->role)->toBe(UserRole::Recepcao)
        ->and($maria->status)->toBe(UserStatus::Pendente)
        ->and($maria->confirmacao_token)->not->toBeNull()
        ->and($maria->matricula)->toStartWith('EQ');
});

test('lista de socios mostra so socios e a equipe fica na propria tela', function () {
    [$tenant, $admin] = clubeComAdmin();
    User::factory()->create(['tenant_id' => $tenant->id, 'nome' => 'Joao Socio', 'role' => UserRole::Jogador]);
    User::factory()->create(['tenant_id' => $tenant->id, 'nome' => 'Paula Recepcao', 'role' => UserRole::Recepcao]);

    Livewire::actingAs($admin)->test(ListaSocios::class)
        ->assertSee('Joao Socio')
        ->assertDontSee('Paula Recepcao');

    Livewire::actingAs($admin)->test(ListaEquipe::class)
        ->assertSee('Paula Recepcao')
        ->assertDontSee('Joao Socio');
});

test('so administrador abre a tela de equipe', function () {
    [$tenant] = clubeComAdmin();
    $recepcao = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Recepcao]);

    $this->actingAs($recepcao)->get(route('equipe.index'))->assertForbidden();
});

test('limite de equipe definido pelo master trava novos usuarios da equipe', function () {
    [$tenant, $admin] = clubeComAdmin(['limite_equipe' => 2]);

    $tela = Livewire::actingAs($admin)->test(ListaEquipe::class);

    $tela->call('novo')->set('nome', 'Primeira')->set('email', 'primeira@clube.com.br')->set('role', 'recepcao')
        ->call('salvar')->assertHasNoErrors();

    $tela->call('novo')->set('nome', 'Segundo')->set('email', 'segundo@clube.com.br')->set('role', 'professor')
        ->call('salvar')->assertHasErrors('limite');

    expect(User::query()->where('email', 'segundo@clube.com.br')->exists())->toBeFalse();
});

test('limite de socios trava cadastro manual e a importacao relata o motivo', function () {
    [$tenant, $admin] = clubeComAdmin(['limite_socios' => 1]);
    $acao = app(SalvarSocio::class);

    $dados = fn (string $n): array => [
        'nome' => "Socio {$n}", 'matricula' => "M{$n}", 'email' => "socio{$n}@clube.com.br", 'role' => 'jogador',
    ];

    $acao->handle($admin, $dados('1'));

    expect(fn () => $acao->handle($admin, $dados('2')))->toThrow(ValidationException::class);
    expect(User::query()->where('role', UserRole::Jogador)->count())->toBe(1);
});

test('master ajusta plano e limites do clube pelo painel', function () {
    $master = User::factory()->administrador()->create(['super_admin' => true]);
    $tenant = Tenant::factory()->create(['teste_gratis' => true]);

    Livewire::actingAs($master)
        ->test(ClubesPendentes::class)
        ->assertSee('Em teste grátis')
        ->call('editarExpiracao', $tenant->id)
        ->set('novaExpiracao', now()->addDays(45)->toDateString())
        ->set('novoTeste', false)
        ->set('novoLimiteEquipe', '8')
        ->set('novoLimiteSocios', '300')
        ->call('salvarExpiracao')
        ->assertHasNoErrors();

    $tenant->refresh();

    expect($tenant->teste_gratis)->toBeFalse()
        ->and($tenant->limite_equipe)->toBe(8)
        ->and($tenant->limite_socios)->toBe(300);

    Livewire::actingAs($master)
        ->test(ClubesPendentes::class)
        ->call('editarExpiracao', $tenant->id)
        ->set('novoLimiteSocios', '')
        ->call('salvarExpiracao');

    expect($tenant->fresh()->limite_socios)->toBeNull();
});

test('quem ja esta logado perde o acesso quando o teste do clube vence', function () {
    Carbon::setTestNow('2026-11-11 10:00:00');
    [$tenant, $admin] = clubeComAdmin(['teste_gratis' => true, 'expira_em' => now()->addDays(2)]);

    $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('Teste grátis');

    Carbon::setTestNow('2026-11-20 10:00:00');

    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();

    Carbon::setTestNow();
});
