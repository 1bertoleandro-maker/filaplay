<?php

declare(strict_types=1);

use App\Domains\Clube\Actions\AtualizarClube;
use App\Domains\Clube\Livewire\EditarClube;
use App\Domains\Clube\Models\Tenant;
use App\Domains\Jogadores\Actions\SalvarSocio;
use App\Domains\Jogadores\Livewire\ListaSocios;
use App\Domains\Jogadores\Models\User;
use App\Domains\Presenca\Enums\PresencaMetodo;
use App\Domains\Presenca\Models\Presenca;
use App\Domains\Quadras\Actions\SalvarQuadra;
use App\Domains\Quadras\Enums\QuadraStatus;
use App\Domains\Quadras\Enums\TipoPiso;
use App\Domains\Quadras\Livewire\ListaQuadras;
use App\Domains\Quadras\Models\Quadra;
use App\Domains\Reservas\Actions\CriarReserva;
use App\Domains\Reservas\Enums\ReservaOrigem;
use App\Domains\Reservas\Enums\ReservaStatus;
use App\Domains\Reservas\Livewire\ListaReservas;
use App\Domains\Reservas\Models\Reserva;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function clubeComContexto(): array
{
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->administrador()->create(['tenant_id' => $tenant->id]);
    app(TenantContext::class)->set($tenant->id);

    return [$tenant->fresh(), $admin];
}

test('administrador atualiza horarios do clube', function () {
    [$tenant, $admin] = clubeComContexto();

    $horarios = [];
    foreach (['segunda', 'terca', 'quarta', 'quinta', 'sexta'] as $dia) {
        $horarios[$dia] = ['fechado' => false, 'abre' => '08:00', 'fecha' => '20:00'];
    }
    $horarios['sabado'] = ['fechado' => false, 'abre' => '09:00', 'fecha' => '14:00'];
    $horarios['domingo'] = ['fechado' => true, 'abre' => '07:00', 'fecha' => '22:00'];

    $atualizado = app(AtualizarClube::class)->handle($admin, $tenant, [
        'nome' => 'Arena Teste',
        'endereco' => 'Rua 1',
        'telefone' => '1199999',
        'email' => 'clube@teste.com',
        'raio_gps_metros' => 180,
        'horarios' => $horarios,
    ]);

    expect($atualizado->nome)->toBe('Arena Teste')
        ->and($atualizado->horario_funcionamento['domingo']['fechado'])->toBeTrue()
        ->and($atualizado->horario_funcionamento['segunda']['abre'])->toBe('08:00');
});

test('busca de cep preenche endereco e numero dispara geolocalizacao automatica', function () {
    [$tenant, $admin] = clubeComContexto();

    Http::fake([
        'viacep.com.br/*' => Http::response([
            'logradouro' => 'Rua das Quadras',
            'bairro' => 'Centro',
            'localidade' => 'São Paulo',
            'uf' => 'SP',
        ], 200),
        'nominatim.openstreetmap.org/*' => Http::response([
            ['lat' => '-23.5505200', 'lon' => '-46.6333090'],
        ], 200),
    ]);

    Livewire::actingAs($admin)
        ->test(EditarClube::class)
        ->set('cep', '01001-000')
        ->call('buscarCep')
        ->assertSet('endereco', 'Rua das Quadras')
        ->assertSet('bairro', 'Centro')
        ->assertSet('cidade', 'São Paulo')
        ->assertSet('estado', 'SP')
        ->assertSet('localizacaoStatus', 'ok')
        ->set('numero', '100')
        ->call('atualizarGeolocalizacao')
        ->assertSet('latitude', -23.55052)
        ->assertSet('longitude', -46.633309)
        ->assertSet('localizacaoStatus', 'ok')
        ->call('salvar')
        ->assertHasNoErrors();

    $tenant->refresh();

    expect($tenant->cep)->toBe('01001-000')
        ->and($tenant->numero)->toBe('100')
        ->and($tenant->bairro)->toBe('Centro')
        ->and($tenant->cidade)->toBe('São Paulo')
        ->and($tenant->estado)->toBe('SP')
        ->and((float) $tenant->latitude)->toBe(-23.5505200)
        ->and((float) $tenant->longitude)->toBe(-46.6333090);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'viacep.com.br'));
    Http::assertSent(fn ($request) => str_contains($request->url(), 'nominatim.openstreetmap.org'));
});

test('cep invalido nao encontrado mostra mensagem de erro', function () {
    [, $admin] = clubeComContexto();

    Http::fake([
        'viacep.com.br/*' => Http::response(['erro' => true], 200),
    ]);

    Livewire::actingAs($admin)
        ->test(EditarClube::class)
        ->set('cep', '00000-000')
        ->call('buscarCep')
        ->assertSet('localizacaoStatus', 'erro');
});

test('logo do clube aparece no ambiente quando a opcao esta ligada', function () {
    Storage::fake('public');
    [$tenant, $admin] = clubeComContexto();
    $caminho = 'clubes/'.$tenant->id.'/logo.png';
    Storage::disk('public')->put($caminho, 'fake-logo');
    $tenant->update(['logo' => $caminho, 'exibir_logo' => true, 'nome' => 'Arena Verde']);

    $url = Storage::disk('public')->url($caminho);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee($url, false)
        ->assertSee('Arena Verde');

    Livewire::actingAs($admin)
        ->test(EditarClube::class)
        ->set('exibir_logo', false)
        ->call('salvar')
        ->assertHasNoErrors();

    expect($tenant->fresh()->exibir_logo)->toBeFalse();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee($url, false);
});

test('quadra e salva com foto', function () {
    [, $admin] = clubeComContexto();
    Storage::fake('public');

    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
    $tmp = tempnam(sys_get_temp_dir(), 'qd').'.png';
    file_put_contents($tmp, $png);
    $foto = new UploadedFile($tmp, 'quadra.png', 'image/png', null, true);

    $quadra = app(SalvarQuadra::class)->handle($admin, [
        'nome' => 'Central',
        'apelido' => 'Showcourt',
        'tipo_piso' => TipoPiso::Saibro->value,
        'coberta' => true,
        'iluminacao' => true,
        'status' => QuadraStatus::Disponivel->value,
        'ordem_exibicao' => 1,
    ], $foto);

    expect($quadra->nome)->toBe('Central')
        ->and($quadra->foto_path)->not->toBeNull()
        ->and(Storage::disk('public')->exists($quadra->foto_path))->toBeTrue();
});

test('recepcao cadastra socio', function () {
    $tenant = Tenant::factory()->create();
    $recepcao = User::factory()->recepcao()->create(['tenant_id' => $tenant->id]);
    app(TenantContext::class)->set($tenant->id);

    $socio = app(SalvarSocio::class)->handle($recepcao, [
        'nome' => 'Maria Sócia',
        'matricula' => 'SOC100',
        'telefone' => '1198888',
        'email' => 'maria.socia@teste.com',
        'role' => 'jogador',
        'nivel' => 'iniciante',
    ]);

    expect($socio->nome)->toBe('Maria Sócia')
        ->and($socio->matricula)->toBe('SOC100')
        ->and($socio->cadastro_facial_completo)->toBeFalse();
});

test('secretaria confirma reserva dentro do horario', function () {
    [$tenant, $admin] = clubeComContexto();
    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id]);
    $socio = User::factory()->create(['tenant_id' => $tenant->id]);

    $inicio = now()->copy()->next(Carbon::MONDAY)->setTime(10, 0);

    $reserva = app(CriarReserva::class)->handle(
        $admin,
        $quadra->id,
        $socio->id,
        $inicio->toDateString(),
        $inicio->format('H:i'),
        60,
        'secretaria',
    );

    expect($reserva->status)->toBe(ReservaStatus::Confirmada)
        ->and($reserva->observacoes)->toContain('secretaria')
        ->and(Presenca::query()->where('user_id', $socio->id)->where('metodo', PresencaMetodo::Tablet)->exists())->toBeTrue();
});

test('reserva fora do horario e rejeitada', function () {
    [$tenant, $admin] = clubeComContexto();
    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id]);
    $socio = User::factory()->create(['tenant_id' => $tenant->id]);

    $domingo = Carbon::parse('next sunday')->setTime(16, 0);

    expect(fn () => app(CriarReserva::class)->handle(
        $admin,
        $quadra->id,
        $socio->id,
        $domingo->toDateString(),
        '16:00',
        60,
        'secretaria',
    ))->toThrow(ValidationException::class);
});

test('nao permite overlap de reservas confirmadas', function () {
    [$tenant, $admin] = clubeComContexto();
    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id]);
    $socio = User::factory()->create(['tenant_id' => $tenant->id]);
    $inicio = now()->copy()->next(Carbon::TUESDAY)->setTime(11, 0);

    app(CriarReserva::class)->handle($admin, $quadra->id, $socio->id, $inicio->toDateString(), '11:00', 60, 'secretaria');

    expect(fn () => app(CriarReserva::class)->handle(
        $admin,
        $quadra->id,
        $socio->id,
        $inicio->toDateString(),
        '11:30',
        60,
        'secretaria',
    ))->toThrow(ValidationException::class);
});

test('reconhecimento facial aceita a mesma foto e recusa outra', function () {
    [$tenant, $admin] = clubeComContexto();
    Storage::fake('public');

    $pngIgual = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
    $pngOutra = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);

    Storage::disk('public')->put('faces/ref.png', $pngIgual);
    $socio = User::factory()->create([
        'tenant_id' => $tenant->id,
        'face_photo_path' => 'faces/ref.png',
        'cadastro_facial_completo' => true,
    ]);
    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id]);

    $tmpOk = tempnam(sys_get_temp_dir(), 'ok').'.png';
    file_put_contents($tmpOk, $pngIgual);
    $fotoOk = new UploadedFile($tmpOk, 'ok.png', 'image/png', null, true);

    $inicio = now()->copy()->next(Carbon::WEDNESDAY)->setTime(9, 0);

    $reserva = app(CriarReserva::class)->handle(
        $admin,
        $quadra->id,
        $socio->id,
        $inicio->toDateString(),
        '09:00',
        60,
        'facial',
        $fotoOk,
    );

    expect($reserva->observacoes)->toContain('facial')
        ->and(Presenca::query()->where('user_id', $socio->id)->where('metodo', PresencaMetodo::Facial)->exists())->toBeTrue();

    $tmpBad = tempnam(sys_get_temp_dir(), 'bad').'.png';
    file_put_contents($tmpBad, $pngOutra);
    $fotoBad = new UploadedFile($tmpBad, 'bad.png', 'image/png', null, true);

    expect(fn () => app(CriarReserva::class)->handle(
        $admin,
        $quadra->id,
        $socio->id,
        $inicio->copy()->addHours(2)->toDateString(),
        '11:00',
        60,
        'facial',
        $fotoBad,
    ))->toThrow(ValidationException::class);
});

test('jogador nao acessa cadastros operacionais', function () {
    $jogador = User::factory()->create();

    $this->actingAs($jogador)
        ->get(route('clube.editar'))
        ->assertForbidden();

    $this->actingAs($jogador)
        ->get(route('reservas.index'))
        ->assertForbidden();

    $this->actingAs($jogador)
        ->get(route('tv.painel'))
        ->assertForbidden();
});

test('recepcao acessa clube e quadras para cadastrar', function () {
    $recepcao = User::factory()->recepcao()->create();

    $this->actingAs($recepcao)
        ->get(route('clube.editar'))
        ->assertOk()
        ->assertSee('Cadastro da empresa ou clube');

    $this->actingAs($recepcao)
        ->get(route('quadras.index'))
        ->assertOk()
        ->assertSee('Cadastro da quadra');

    $this->actingAs($recepcao)
        ->get(route('socios.index'))
        ->assertOk()
        ->assertSee('Novo sócio');
});

test('admin ve os novos modulos', function () {
    $admin = User::factory()->administrador()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Cadastrar empresa / clube')
        ->assertSee('Painel da TV');

    $this->actingAs($admin)
        ->get(route('tv.painel'))
        ->assertOk()
        ->assertSeeText('FilaPlay');

    $quadra = Quadra::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'nome' => 'Quadra Show',
        'apelido' => 'Central',
    ]);
    $socio = User::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'nome' => 'Jogador TV',
    ]);
    Reserva::query()->create([
        'tenant_id' => $admin->tenant_id,
        'quadra_id' => $quadra->id,
        'user_id' => $socio->id,
        'inicio' => now()->subMinutes(10),
        'fim' => now()->addMinutes(50),
        'origem' => ReservaOrigem::Recepcao,
        'status' => ReservaStatus::Confirmada,
    ]);
    app(TenantContext::class)->set($admin->tenant_id);

    $this->actingAs($admin)
        ->get(route('tv.painel'))
        ->assertOk()
        ->assertSee('Central')
        ->assertSee('Jogador')
        ->assertSee('Reservado');
});

test('telas livewire salvam clube quadra socio e reserva', function () {
    [$tenant, $admin] = clubeComContexto();
    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id, 'nome' => 'Pista A']);
    $socio = User::factory()->create(['tenant_id' => $tenant->id]);
    $inicio = now()->copy()->next(Carbon::THURSDAY)->setTime(15, 0);

    Livewire::actingAs($admin)
        ->test(EditarClube::class)
        ->set('telefone', '(11) 90000-0000')
        ->call('salvar')
        ->assertHasNoErrors();

    expect($tenant->fresh()->telefone)->toBe('(11) 90000-0000');

    Livewire::actingAs($admin)
        ->test(ListaQuadras::class)
        ->call('nova')
        ->assertSet('formAberto', true)
        ->set('nome', 'Pista Extra')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertSet('formAberto', true);

    Livewire::actingAs($admin)
        ->test(ListaSocios::class)
        ->call('novo')
        ->set('nome', 'Cliente Novo')
        ->set('matricula', 'NOV999')
        ->set('email', 'cliente.novo@teste.com')
        ->set('role', 'jogador')
        ->call('salvar')
        ->assertHasNoErrors();

    Livewire::actingAs($admin)
        ->test(ListaReservas::class)
        ->call('nova')
        ->set('quadra_id', $quadra->id)
        ->set('user_id', $socio->id)
        ->set('data', $inicio->toDateString())
        ->set('hora', '15:00')
        ->set('validacao', 'secretaria')
        ->call('salvar')
        ->assertHasNoErrors();

    expect(Reserva::query()->where('quadra_id', $quadra->id)->exists())->toBeTrue();
});
