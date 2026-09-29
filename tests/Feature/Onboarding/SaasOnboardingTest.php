<?php

declare(strict_types=1);

use App\Domains\Clube\Actions\AprovarClube;
use App\Domains\Clube\Actions\CadastrarNovoClube;
use App\Domains\Clube\Actions\CriarClubePeloAdmin;
use App\Domains\Clube\Enums\TenantStatus;
use App\Domains\Clube\Livewire\ClubesPendentes;
use App\Domains\Clube\Models\Tenant;
use App\Domains\Jogadores\Actions\SalvarSocio;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Enums\UserStatus;
use App\Domains\Jogadores\Livewire\ConfirmarCadastro;
use App\Domains\Jogadores\Models\User;
use App\Domains\Quadras\Models\Quadra;
use App\Domains\Reservas\Actions\ReservarPelaTablet;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('novo clube nasce pendente e nao consegue entrar antes da aprovacao', function () {
    $tenant = app(CadastrarNovoClube::class)->handle([
        'nome_clube' => 'Clube Novo',
        'nome_responsavel' => 'Fulano Responsável',
        'email' => 'fulano@clubenovo.com.br',
        'telefone' => '11999990000',
        'senha' => 'senha12345',
    ]);

    expect($tenant->status)->toBe(TenantStatus::Pendente);

    $component = Volt::test('pages.auth.login')
        ->set('form.email', 'fulano@clubenovo.com.br')
        ->set('form.password', 'senha12345');

    $component->call('login');

    $component->assertHasErrors('form.email');
    expect(auth()->check())->toBeFalse();
});

test('super admin aprova o clube e o responsavel passa a entrar', function () {
    $tenant = app(CadastrarNovoClube::class)->handle([
        'nome_clube' => 'Clube Aprovado',
        'nome_responsavel' => 'Ciclana Responsável',
        'email' => 'ciclana@clubeaprovado.com.br',
        'telefone' => null,
        'senha' => 'senha12345',
    ]);

    $superAdmin = User::factory()->administrador()->create(['super_admin' => true]);

    app(AprovarClube::class)->aprovar($superAdmin, $tenant);

    expect($tenant->fresh()->status)->toBe(TenantStatus::Ativo);

    $component = Volt::test('pages.auth.login')
        ->set('form.email', 'ciclana@clubeaprovado.com.br')
        ->set('form.password', 'senha12345');

    $component->call('login');

    $component->assertHasNoErrors();
    expect(auth()->check())->toBeTrue();
});

test('apenas super admin acessa o painel de clubes', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->administrador()->create(['tenant_id' => $tenant->id, 'super_admin' => false]);

    $this->actingAs($admin)->get(route('admin.clubes'))->assertForbidden();

    $superAdmin = User::factory()->administrador()->create(['super_admin' => true]);

    Livewire::actingAs($superAdmin)
        ->test(ClubesPendentes::class)
        ->assertOk();
});

test('cadastro de socio sem foto gera convite pendente e o link ativa a conta', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->administrador()->create(['tenant_id' => $tenant->id]);
    app(TenantContext::class)->set($tenant->id);

    $socio = app(SalvarSocio::class)->handle($admin, [
        'nome' => 'Sócio Convidado',
        'matricula' => 'CONV001',
        'telefone' => '11988887777',
        'email' => 'socio.convidado@teste.com',
        'role' => UserRole::Jogador->value,
        'nivel' => null,
    ]);

    expect($socio->status)->toBe(UserStatus::Pendente)
        ->and($socio->confirmacao_token)->not->toBeNull();

    Storage::fake('public');

    $foto = UploadedFile::fake()->create('rosto.jpg', 10, 'image/jpeg');

    Livewire::test(ConfirmarCadastro::class, ['token' => $socio->confirmacao_token])
        ->set('senha', 'novaSenha123')
        ->set('senha_confirmation', 'novaSenha123')
        ->set('foto', $foto)
        ->call('confirmar');

    $socio->refresh();

    expect($socio->status)->toBe(UserStatus::Ativo)
        ->and($socio->cadastro_facial_completo)->toBeTrue()
        ->and($socio->confirmacao_token)->toBeNull()
        ->and(auth()->check())->toBeTrue();
});

test('reserva pelo tablet identifica os dois jogadores pelo codigo e salva', function () {
    $tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($tenant->id);

    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id]);
    $principal = User::factory()->create(['tenant_id' => $tenant->id, 'matricula' => 'JOG100']);
    $parceiro = User::factory()->create(['tenant_id' => $tenant->id, 'matricula' => 'JOG200']);

    $hora = now()->addHour()->format('H:i');

    $reserva = app(ReservarPelaTablet::class)->handle(
        $quadra,
        'JOG100',
        'JOG200',
        now()->toDateString(),
        $hora,
    );

    expect($reserva->user_id)->toBe($principal->id)
        ->and($reserva->quadra_id)->toBe($quadra->id)
        ->and($reserva->observacoes)->toContain($parceiro->nome)
        ->and($reserva->jogadores)->toHaveCount(2);
});

test('super admin cria cliente ja ativo e o responsavel recebe convite sem exigir foto', function () {
    $master = User::factory()->administrador()->create(['super_admin' => true]);

    $tenant = app(CriarClubePeloAdmin::class)->handle($master, [
        'nome_clube' => 'Clube Criado Pelo Master',
        'nome_responsavel' => 'Responsável Convidado',
        'email' => 'responsavel@clubemaster.com.br',
        'telefone' => '11977776666',
        'expira_em' => now()->addYear()->toDateString(),
    ]);

    expect($tenant->status)->toBe(TenantStatus::Ativo)
        ->and($tenant->expira_em)->not->toBeNull();

    $responsavel = User::query()->where('email', 'responsavel@clubemaster.com.br')->firstOrFail();

    expect($responsavel->status)->toBe(UserStatus::Pendente)
        ->and($responsavel->role)->toBe(UserRole::Administrador)
        ->and($responsavel->confirmacao_token)->not->toBeNull();

    // Como não é jogador, confirma sem precisar enviar foto.
    Livewire::test(ConfirmarCadastro::class, ['token' => $responsavel->confirmacao_token])
        ->set('senha', 'senhaSegura123')
        ->set('senha_confirmation', 'senhaSegura123')
        ->call('confirmar')
        ->assertHasNoErrors();

    $responsavel->refresh();

    expect($responsavel->status)->toBe(UserStatus::Ativo)
        ->and($responsavel->confirmacao_token)->toBeNull()
        ->and(auth()->check())->toBeTrue();
});

test('apenas super admin pode criar cliente pelo painel', function () {
    $naoMaster = User::factory()->administrador()->create(['super_admin' => false]);

    expect(fn () => app(CriarClubePeloAdmin::class)->handle($naoMaster, [
        'nome_clube' => 'X',
        'nome_responsavel' => 'Y',
        'email' => 'z@z.com',
    ]))->toThrow(HttpException::class);
});

test('clube com acesso expirado nao consegue entrar mesmo estando ativo', function () {
    $tenant = Tenant::factory()->create([
        'expira_em' => now()->subDay(),
    ]);
    $admin = User::factory()->administrador()->create([
        'tenant_id' => $tenant->id,
        'email' => 'expirado@clube.com.br',
    ]);

    expect($tenant->expirado())->toBeTrue();

    $component = Volt::test('pages.auth.login')
        ->set('form.email', 'expirado@clube.com.br')
        ->set('form.password', 'password');

    $component->call('login');

    $component->assertHasErrors('form.email');
    expect(auth()->check())->toBeFalse();
});

test('super admin atualiza a validade de um clube pelo painel', function () {
    $master = User::factory()->administrador()->create(['super_admin' => true]);
    $tenant = Tenant::factory()->create();

    Livewire::actingAs($master)
        ->test(ClubesPendentes::class)
        ->call('editarExpiracao', $tenant->id)
        ->set('novaExpiracao', now()->addMonths(6)->toDateString())
        ->call('salvarExpiracao')
        ->assertHasNoErrors();

    expect($tenant->fresh()->expira_em)->not->toBeNull();
});

test('reserva pelo tablet rejeita codigo inexistente', function () {
    $tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($tenant->id);

    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id]);

    app(ReservarPelaTablet::class)->handle(
        $quadra,
        'NAOEXISTE',
        null,
        now()->toDateString(),
        now()->addHour()->format('H:i'),
    );
})->throws(ValidationException::class);

test('tablet mostra a grade e secretaria reserva pelo codigo sem facial', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-23 10:04:00'));

    $tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($tenant->id);
    $admin = User::factory()->administrador()->create(['tenant_id' => $tenant->id]);
    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id, 'apelido' => 'Central']);
    User::factory()->create(['tenant_id' => $tenant->id, 'matricula' => 'AA11', 'nome' => 'Ana Clara']);
    User::factory()->create(['tenant_id' => $tenant->id, 'matricula' => 'BB22', 'nome' => 'Bruno Silva']);

    Livewire::actingAs($admin)
        ->test(\App\Domains\Reservas\Livewire\ReservaTablet::class, ['modo' => 'secretaria'])
        ->assertSee('Agenda de hoje')
        ->assertSee('Central')
        ->call('abrir', $quadra->id)
        ->assertSet('painelAberto', true)
        ->assertSet('hora', '10:05')
        ->assertSet('horaFim', '11:05')
        ->call('escolherModalidade', 'simples')
        ->set('codigo', 'AA11')
        ->call('identificarPorCodigo')
        ->assertSet('identificado.nome', 'Ana Clara')
        ->call('confirmarJogador')
        ->set('codigo', 'BB22')
        ->call('identificarPorCodigo')
        ->call('confirmarJogador')
        ->call('salvar')
        ->assertSet('erro', false);

    $reserva = \App\Domains\Reservas\Models\Reserva::query()->where('quadra_id', $quadra->id)->first();

    expect($reserva)->not->toBeNull()
        ->and($reserva->inicio->format('H:i'))->toBe('10:05')
        ->and($reserva->fim->format('H:i'))->toBe('11:05');
});

test('proxima reserva na mesma quadra encadeia no fim da anterior', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-23 10:04:00'));

    $tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($tenant->id);
    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id]);
    User::factory()->create(['tenant_id' => $tenant->id, 'matricula' => 'P1']);
    User::factory()->create(['tenant_id' => $tenant->id, 'matricula' => 'P2']);
    User::factory()->create(['tenant_id' => $tenant->id, 'matricula' => 'P3']);
    User::factory()->create(['tenant_id' => $tenant->id, 'matricula' => 'P4']);

    $primeira = app(ReservarPelaTablet::class)->handle($quadra, 'P1', 'P2', now()->toDateString(), null);
    $segunda = app(ReservarPelaTablet::class)->handle($quadra, 'P3', 'P4', now()->toDateString(), null);

    expect($primeira->inicio->format('H:i'))->toBe('10:05')
        ->and($primeira->fim->format('H:i'))->toBe('11:05')
        ->and($segunda->inicio->format('H:i'))->toBe('11:05')
        ->and($segunda->fim->format('H:i'))->toBe('12:05');
});

test('tablet exige facial quando o parametro do clube esta ativo e secretaria nao', function () {
    Storage::fake('public');
    Carbon::setTestNow(Carbon::parse('2026-09-23 10:04:00'));

    $tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($tenant->id);
    app(\App\Domains\Configuracoes\Services\ConfiguracaoService::class)
        ->set(\App\Domains\Configuracoes\ConfiguracaoChave::TABLET_EXIGIR_FACIAL, ['ativo' => true]);

    $admin = User::factory()->administrador()->create(['tenant_id' => $tenant->id]);
    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id]);
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
    Storage::disk('public')->put('faces/ana.png', $png);

    User::factory()->create([
        'tenant_id' => $tenant->id,
        'matricula' => 'F1',
        'nome' => 'Ana Facial',
        'face_photo_path' => 'faces/ana.png',
        'cadastro_facial_completo' => true,
    ]);
    User::factory()->create([
        'tenant_id' => $tenant->id,
        'matricula' => 'F2',
        'nome' => 'Bruno Facial',
        'cadastro_facial_completo' => false,
    ]);

    // Tablet: sem reconhecimento automático, botão manual não libera (precisa olhar a câmera).
    Livewire::actingAs($admin)
        ->test(\App\Domains\Reservas\Livewire\ReservaTablet::class, ['modo' => 'tablet'])
        ->call('abrir', $quadra->id)
        ->call('escolherModalidade', 'simples')
        ->set('codigo', 'F1')
        ->call('identificarPorCodigo')
        ->call('confirmarJogador')
        ->assertHasErrors('foto_facial');

    // Tablet: catraca libera via confirmarJogadorPorFacial.
    Livewire::actingAs($admin)
        ->test(\App\Domains\Reservas\Livewire\ReservaTablet::class, ['modo' => 'tablet'])
        ->call('abrir', $quadra->id)
        ->call('escolherModalidade', 'simples')
        ->set('codigo', 'F1')
        ->call('identificarPorCodigo')
        ->call('confirmarJogadorPorFacial')
        ->assertHasNoErrors()
        ->assertSet('jogadores.0.matricula', 'F1');

    // Secretaria: confere foto e segue sem câmera.
    Livewire::actingAs($admin)
        ->test(\App\Domains\Reservas\Livewire\ReservaTablet::class, ['modo' => 'secretaria'])
        ->call('abrir', $quadra->id)
        ->call('escolherModalidade', 'simples')
        ->set('codigo', 'F1')
        ->call('identificarPorCodigo')
        ->call('confirmarJogador')
        ->assertHasNoErrors()
        ->assertSet('jogadores.0.matricula', 'F1');
});

test('mesmo socio nao reserva em outra quadra enquanto o horario nao acaba', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-23 10:04:00'));

    $tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($tenant->id);
    $quadraA = Quadra::factory()->create(['tenant_id' => $tenant->id, 'apelido' => 'Central']);
    $quadraB = Quadra::factory()->create(['tenant_id' => $tenant->id, 'apelido' => 'Lateral']);
    User::factory()->create(['tenant_id' => $tenant->id, 'matricula' => 'S1', 'nome' => 'Ana Souza']);
    User::factory()->create(['tenant_id' => $tenant->id, 'matricula' => 'S2', 'nome' => 'Bruno Lima']);
    User::factory()->create(['tenant_id' => $tenant->id, 'matricula' => 'S3', 'nome' => 'Carla Dias']);

    app(ReservarPelaTablet::class)->handle($quadraA, 'S1', 'S2', now()->toDateString(), null);

    try {
        app(ReservarPelaTablet::class)->handle($quadraB, 'S1', 'S3', now()->toDateString(), null);
        expect(false)->toBeTrue('deveria bloquear o sócio ocupado');
    } catch (ValidationException $e) {
        expect(collect($e->errors())->flatten()->first())
            ->toContain('Ana ainda está na Central até 11:05');
    }
});

test('clube envia link de reconhecimento facial e o socio cadastra a foto', function () {
    Storage::fake('public');
    $tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($tenant->id);
    $socio = User::factory()->create([
        'tenant_id' => $tenant->id,
        'cadastro_facial_completo' => false,
        'email' => 'socio.facial@teste.com',
    ]);

    $enviado = app(\App\Domains\Jogadores\Actions\EnviarConviteFacial::class)->handle($socio);

    expect($enviado->facial_token)->not->toBeNull();

    Livewire::test(\App\Domains\Jogadores\Livewire\CadastrarFacial::class, ['token' => $enviado->facial_token])
        ->set('foto', UploadedFile::fake()->image('rosto.jpg'))
        ->call('salvar')
        ->assertSet('concluido', true);

    expect($socio->fresh()->cadastro_facial_completo)->toBeTrue()
        ->and($socio->fresh()->facial_token)->toBeNull();
});

test('secretaria cadastra o reconhecimento facial do socio na hora', function () {
    Storage::fake('public');
    $tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($tenant->id);
    $recepcao = User::factory()->recepcao()->create(['tenant_id' => $tenant->id]);
    $socio = User::factory()->create([
        'tenant_id' => $tenant->id,
        'nome' => 'Carla Quadra',
        'cadastro_facial_completo' => false,
    ]);

    Livewire::actingAs($recepcao)
        ->test(\App\Domains\Jogadores\Livewire\ListaSocios::class)
        ->call('abrirFacialBalcao', $socio->id)
        ->assertSet('facialNome', 'Carla Quadra')
        ->set('faceBalcao', UploadedFile::fake()->image('rosto-balcao.jpg'))
        ->call('salvarFacialBalcao')
        ->assertHasNoErrors();

    expect($socio->fresh()->cadastro_facial_completo)->toBeTrue()
        ->and($socio->fresh()->face_photo_path)->not->toBeNull()
        ->and($socio->fresh()->status)->toBe(UserStatus::Ativo);
});
