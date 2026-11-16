<?php

declare(strict_types=1);

use App\Domains\Clube\Models\Tenant;
use App\Domains\Configuracoes\ConfiguracaoChave;
use App\Domains\Configuracoes\Services\ConfiguracaoService;
use App\Domains\Jogadores\Enums\UserStatus;
use App\Domains\Jogadores\Livewire\CadastrarFacial;
use App\Domains\Jogadores\Models\User;
use App\Domains\Quadras\Models\Bloqueio;
use App\Domains\Quadras\Models\Quadra;
use App\Domains\Reservas\Actions\AjustarReservaAoVivo;
use App\Domains\Reservas\Actions\EntrarNoJogo;
use App\Domains\Reservas\Actions\ReservarPelaTablet;
use App\Domains\Reservas\Enums\ReservaStatus;
use App\Domains\Reservas\Livewire\ReservaTablet;
use App\Domains\Reservas\Models\Atividade;
use App\Domains\Reservas\Models\Reserva;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function clubeAoVivo(string $agora = '2026-09-23 10:04:00'): array
{
    Carbon::setTestNow(Carbon::parse($agora));

    $tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($tenant->id);
    $admin = User::factory()->administrador()->create(['tenant_id' => $tenant->id]);

    return [$tenant, $admin];
}

function atletas(Tenant $tenant, array $codigos): void
{
    foreach ($codigos as $codigo) {
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'matricula' => $codigo,
            'nome' => 'Atleta '.$codigo,
        ]);
    }
}

function minutosDe(Reserva $reserva): int
{
    return (int) round(($reserva->fim->getTimestamp() - $reserva->inicio->getTimestamp()) / 60);
}

test('chuva: simples dura 40 minutos e duplas continuam 60', function () {
    [$tenant] = clubeAoVivo();
    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id, 'coberta' => true]);
    atletas($tenant, ['C1', 'C2', 'C3', 'C4', 'C5', 'C6']);
    $tenant->update(['modo_chuva' => true]);

    $simples = app(ReservarPelaTablet::class)->handle($quadra->fresh(), 'C1', 'C2', now()->toDateString(), null);
    $duplas = app(ReservarPelaTablet::class)->handle($quadra->fresh(), 'C3', 'C4', now()->toDateString(), null, 60, 'duplas', false, ['C5', 'C6']);

    expect(minutosDe($simples))->toBe(40)
        ->and(minutosDe($duplas))->toBe(60)
        ->and($duplas->inicio->equalTo($simples->fim))->toBeTrue();
});

test('chuva: quadra descoberta nao aceita reserva', function () {
    [$tenant] = clubeAoVivo();
    $descoberta = Quadra::factory()->create(['tenant_id' => $tenant->id, 'coberta' => false]);
    atletas($tenant, ['D1', 'D2']);
    $tenant->update(['modo_chuva' => true]);

    expect(fn () => app(ReservarPelaTablet::class)->handle($descoberta->fresh(), 'D1', 'D2', now()->toDateString(), null))
        ->toThrow(ValidationException::class);
});

test('secretaria liga a chuva pelo botao rapido e o evento aparece na linha do tempo', function () {
    [$tenant, $admin] = clubeAoVivo();
    Quadra::factory()->create(['tenant_id' => $tenant->id, 'coberta' => false, 'apelido' => 'Aberta']);

    Livewire::actingAs($admin)
        ->test(ReservaTablet::class, ['modo' => 'secretaria'])
        ->call('alternarChuva')
        ->assertSee('Modo chuva ligado')
        ->assertSee('Chuva');

    expect($tenant->fresh()->modo_chuva)->toBeTrue()
        ->and(Atividade::query()->where('tipo', 'chuva_ligada')->exists())->toBeTrue();
});

test('secretaria soma minutos e a proxima reserva e empurrada', function () {
    [$tenant, $admin] = clubeAoVivo();
    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id]);
    atletas($tenant, ['E1', 'E2', 'E3', 'E4']);

    $primeira = app(ReservarPelaTablet::class)->handle($quadra, 'E1', 'E2', now()->toDateString(), null);
    $segunda = app(ReservarPelaTablet::class)->handle($quadra, 'E3', 'E4', now()->toDateString(), null);

    app(AjustarReservaAoVivo::class)->adicionarMinutos($primeira, 10, $admin);

    expect($primeira->fresh()->fim->format('H:i'))->toBe('11:15')
        ->and($segunda->fresh()->inicio->format('H:i'))->toBe('11:15')
        ->and($segunda->fresh()->fim->format('H:i'))->toBe('12:15')
        ->and(Atividade::query()->where('tipo', 'tempo_extra')->exists())->toBeTrue();
});

test('atletas que sairam liberam a quadra e a fila anda', function () {
    [$tenant, $admin] = clubeAoVivo();
    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id]);
    atletas($tenant, ['F1', 'F2', 'F3', 'F4']);

    $primeira = app(ReservarPelaTablet::class)->handle($quadra, 'F1', 'F2', now()->toDateString(), null);
    $segunda = app(ReservarPelaTablet::class)->handle($quadra, 'F3', 'F4', now()->toDateString(), null);

    Carbon::setTestNow(Carbon::parse('2026-09-23 10:30:00'));

    app(AjustarReservaAoVivo::class)->liberarQuadra($primeira, $admin);

    expect($primeira->fresh()->status)->toBe(ReservaStatus::Concluida)
        ->and($segunda->fresh()->inicio->format('H:i'))->toBe('10:30')
        ->and($segunda->fresh()->fim->format('H:i'))->toBe('11:30');

    // Os atletas da primeira reserva ficam livres para reservar de novo.
    expect(Reserva::ocupacaoEmOutraQuadra(User::query()->where('matricula', 'F1')->first()))->toBeNull();
});

test('somar minutos respeita o bloqueio de torneio logo depois', function () {
    [$tenant, $admin] = clubeAoVivo();
    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id]);
    atletas($tenant, ['G1', 'G2']);

    $reserva = app(ReservarPelaTablet::class)->handle($quadra, 'G1', 'G2', now()->toDateString(), null);

    Bloqueio::factory()->create([
        'tenant_id' => $tenant->id,
        'quadra_id' => $quadra->id,
        'inicio' => Carbon::parse('2026-09-23 11:05:00'),
        'fim' => Carbon::parse('2026-09-23 14:00:00'),
        'motivo' => 'Torneio interno',
    ]);

    expect(fn () => app(AjustarReservaAoVivo::class)->adicionarMinutos($reserva, 5, $admin))
        ->toThrow(ValidationException::class);
});

test('atleta que chegou depois entra no jogo sem mudar o horario e nao reserva outra quadra', function () {
    [$tenant] = clubeAoVivo();
    $central = Quadra::factory()->create(['tenant_id' => $tenant->id, 'apelido' => 'Central']);
    $lateral = Quadra::factory()->create(['tenant_id' => $tenant->id, 'apelido' => 'Lateral']);
    atletas($tenant, ['H1', 'H2', 'H3', 'H4', 'H5']);

    $reserva = app(ReservarPelaTablet::class)->handle($central, 'H1', 'H2', now()->toDateString(), null);
    $inicio = $reserva->inicio->copy();
    $fim = $reserva->fim->copy();

    $chegou = User::query()->where('matricula', 'H3')->first();
    $atualizada = app(EntrarNoJogo::class)->handle($reserva, $chegou);

    expect($atualizada->elenco())->toHaveCount(3)
        ->and($atualizada->inicio->equalTo($inicio))->toBeTrue()
        ->and($atualizada->fim->equalTo($fim))->toBeTrue();

    // Já está jogando na Central: não consegue reservar a Lateral.
    expect(fn () => app(ReservarPelaTablet::class)->handle($lateral, 'H3', 'H4', now()->toDateString(), null))
        ->toThrow(ValidationException::class);

    // Jogo lotado (4) não aceita o quinto.
    app(EntrarNoJogo::class)->handle($reserva->fresh(), User::query()->where('matricula', 'H4')->first());

    expect(fn () => app(EntrarNoJogo::class)->handle($reserva->fresh(), User::query()->where('matricula', 'H5')->first()))
        ->toThrow(ValidationException::class);
});

test('quem esta jogando em outra quadra nao entra em mais um jogo', function () {
    [$tenant] = clubeAoVivo();
    $central = Quadra::factory()->create(['tenant_id' => $tenant->id, 'apelido' => 'Central']);
    $lateral = Quadra::factory()->create(['tenant_id' => $tenant->id, 'apelido' => 'Lateral']);
    atletas($tenant, ['I1', 'I2', 'I3', 'I4']);

    $naCentral = app(ReservarPelaTablet::class)->handle($central, 'I1', 'I2', now()->toDateString(), null);
    app(ReservarPelaTablet::class)->handle($lateral, 'I3', 'I4', now()->toDateString(), null);

    expect(fn () => app(EntrarNoJogo::class)->handle($naCentral, User::query()->where('matricula', 'I3')->first()))
        ->toThrow(ValidationException::class);
});

test('tablet: atleta entra no jogo pelo codigo e a reserva continua igual', function () {
    [$tenant, $admin] = clubeAoVivo();
    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id, 'apelido' => 'Central']);
    atletas($tenant, ['J1', 'J2', 'J3']);

    $reserva = app(ReservarPelaTablet::class)->handle($quadra, 'J1', 'J2', now()->toDateString(), null);

    Livewire::actingAs($admin)
        ->test(ReservaTablet::class, ['modo' => 'tablet'])
        ->call('abrirEntrarNoJogo', $reserva->id)
        ->assertSet('painelAberto', true)
        ->call('definirMetodo', 'codigo')
        ->set('codigo', 'J3')
        ->call('identificarPorCodigo')
        ->assertSet('identificado.matricula', 'J3')
        ->call('confirmarJogador')
        ->assertSet('painelAberto', false)
        ->assertSee('entrou no jogo');

    expect($reserva->fresh()->elenco())->toHaveCount(3)
        ->and(Atividade::query()->where('tipo', 'jogador_entrou')->exists())->toBeTrue();
});

test('prazo entre atletas: se o segundo nao se cadastra, a reserva em montagem e removida', function () {
    [$tenant, $admin] = clubeAoVivo();
    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id, 'apelido' => 'Central']);
    atletas($tenant, ['K1', 'K2']);

    app(ConfiguracaoService::class)->set(ConfiguracaoChave::TABLET_PRAZO_CADASTRO_SEGUNDOS, ['segundos' => 30]);

    $componente = Livewire::actingAs($admin)
        ->test(ReservaTablet::class, ['modo' => 'tablet'])
        ->call('abrir', $quadra->id)
        ->call('escolherModalidade', 'simples')
        ->call('definirMetodo', 'codigo')
        ->set('codigo', 'K1')
        ->call('identificarPorCodigo')
        ->call('confirmarJogador')
        ->assertSet('jogadores.0.matricula', 'K1');

    expect($componente->get('prazoAte'))->toBe(now()->getTimestamp() + 30);

    Carbon::setTestNow(now()->addSeconds(45));

    $componente
        ->call('expirarPrazo')
        ->assertSet('painelAberto', false)
        ->assertSet('jogadores', [])
        ->assertSet('erro', true)
        ->assertSee('Tempo esgotado');

    expect(Reserva::query()->count())->toBe(0)
        ->and(Atividade::query()->where('tipo', 'cadastro_expirado')->exists())->toBeTrue();
});

test('secretaria acha o atleta digitando o codigo exato e ve a foto antes de confirmar', function () {
    [$tenant, $admin] = clubeAoVivo();
    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id]);
    atletas($tenant, ['L100', 'L1000']);

    Livewire::actingAs($admin)
        ->test(ReservaTablet::class, ['modo' => 'secretaria'])
        ->call('abrir', $quadra->id)
        ->call('escolherModalidade', 'simples')
        ->set('buscaNome', 'L100')
        ->assertSee('Código L100')
        ->call('escolherPrimeiroDaBusca')
        ->assertSet('identificado.matricula', 'L100');
});

test('secretaria registra na quadra um socio com cadastro incompleto e fica anotado', function () {
    [$tenant, $admin] = clubeAoVivo();
    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id]);
    atletas($tenant, ['P1']);
    User::factory()->create([
        'tenant_id' => $tenant->id,
        'matricula' => 'P2',
        'nome' => 'Pendente Silva',
        'status' => UserStatus::Pendente,
        'cadastro_facial_completo' => false,
    ]);

    Livewire::actingAs($admin)
        ->test(ReservaTablet::class, ['modo' => 'secretaria'])
        ->call('abrir', $quadra->id)
        ->call('escolherModalidade', 'simples')
        ->set('buscaNome', 'P1')
        ->call('escolherPrimeiroDaBusca')
        ->call('confirmarJogador')
        ->set('buscaNome', 'P2')
        ->call('escolherPrimeiroDaBusca')
        ->assertSee('Cadastro incompleto')
        ->call('confirmarJogador')
        ->call('salvar')
        ->assertSet('erro', false);

    expect(Reserva::query()->count())->toBe(1)
        ->and(User::query()->where('matricula', 'P2')->first()->status)->toBe(UserStatus::Pendente)
        ->and(Atividade::query()->where('tipo', 'reserva_com_pendencia')->exists())->toBeTrue();
});

test('socio bloqueado por pagamento exige a secretaria confirmar antes de registrar', function () {
    [$tenant, $admin] = clubeAoVivo();
    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id]);
    atletas($tenant, ['B1']);
    User::factory()->bloqueado()->create(['tenant_id' => $tenant->id, 'matricula' => 'B2', 'nome' => 'Devedor Souza']);

    $tela = Livewire::actingAs($admin)
        ->test(ReservaTablet::class, ['modo' => 'secretaria'])
        ->call('abrir', $quadra->id)
        ->call('escolherModalidade', 'simples')
        ->set('buscaNome', 'B1')
        ->call('escolherPrimeiroDaBusca')
        ->call('confirmarJogador')
        ->set('buscaNome', 'B2')
        ->assertSee('Bloqueado')
        ->call('escolherPrimeiroDaBusca')
        ->assertSee('Atenção: sócio bloqueado')
        ->assertSee('Cadastrar mesmo assim');

    // Confirmar direto não passa: precisa do botão "mesmo assim".
    $tela->call('confirmarJogador')->assertHasErrors('codigo');
    expect(Reserva::query()->count())->toBe(0);

    $tela->call('cadastrarMesmoAssim')->call('salvar')->assertSet('erro', false);

    expect(Reserva::query()->count())->toBe(1)
        ->and(Atividade::query()->where('tipo', 'reserva_com_pendencia')->where('detalhe', 'like', '%BLOQUEADO%')->exists())->toBeTrue();
});

test('no tablet do atleta o socio bloqueado ou incompleto e barrado', function () {
    [$tenant] = clubeAoVivo();
    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id]);
    $jogador = User::factory()->create(['tenant_id' => $tenant->id, 'matricula' => 'T1']);
    User::factory()->bloqueado()->create(['tenant_id' => $tenant->id, 'matricula' => 'T2']);
    app(ConfiguracaoService::class)->set(ConfiguracaoChave::TABLET_EXIGIR_FACIAL, ['ativo' => false]);

    Livewire::actingAs($jogador)
        ->test(ReservaTablet::class)
        ->call('abrir', $quadra->id)
        ->call('escolherModalidade', 'simples')
        ->set('codigo', 'T2')
        ->call('identificarPorCodigo')
        ->assertSet('identificado', [])
        ->assertHasErrors('codigo');
});

test('tablet identifica o atleta pelo rosto e ele so confirma', function () {
    [$tenant, $admin] = clubeAoVivo();
    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id]);
    $ana = User::factory()->create([
        'tenant_id' => $tenant->id,
        'matricula' => 'R1',
        'nome' => 'Ana Rosto',
        'face_photo_path' => 'faces/ana.png',
        'cadastro_facial_completo' => true,
    ]);

    $componente = Livewire::actingAs($admin)
        ->test(ReservaTablet::class, ['modo' => 'tablet'])
        ->call('abrir', $quadra->id)
        ->call('escolherModalidade', 'simples')
        ->call('definirMetodo', 'rosto');

    $candidatos = $componente->instance()->carregarCandidatos();
    expect($candidatos)->toHaveCount(1)
        ->and($candidatos[0]['id'])->toBe($ana->id);

    $componente
        ->call('identificarPorRosto', $ana->id)
        ->assertSet('identificado.nome', 'Ana Rosto')
        ->assertSet('identificado.matricula', 'R1')
        ->assertSet('viaRosto', true)
        ->call('confirmarJogador')
        ->assertHasNoErrors()
        ->assertSet('jogadores.0.matricula', 'R1');
});

test('vetor facial calculado no navegador fica guardado e e apagado quando a foto muda', function () {
    [$tenant, $admin] = clubeAoVivo();
    $ana = User::factory()->create([
        'tenant_id' => $tenant->id,
        'face_photo_path' => 'faces/a.png',
        'cadastro_facial_completo' => true,
    ]);

    $vetor = array_map(fn (int $i): float => $i / 1000, range(1, 128));

    Livewire::actingAs($admin)
        ->test(ReservaTablet::class, ['modo' => 'tablet'])
        ->call('salvarDescritor', $ana->id, $vetor);

    expect($ana->fresh()->face_descriptor)->toHaveCount(128);

    $ana->fresh()->update(['face_photo_path' => 'faces/b.png']);

    expect($ana->fresh()->face_descriptor)->toBeNull();
});

test('tv usa alertas ao vivo e a nova reserva aparece nos dados do painel', function () {
    [$tenant, $admin] = clubeAoVivo();
    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id, 'apelido' => 'Central']);
    atletas($tenant, ['T1', 'T2']);

    app(ReservarPelaTablet::class)->handle($quadra, 'T1', 'T2', now()->toDateString(), null);

    $this->actingAs($admin)
        ->get(route('tv.painel'))
        ->assertOk()
        ->assertSee('tv-dados', false)
        ->assertSee('data-novas', false)
        ->assertSee('10:05 às 11:05')
        ->assertSee('Central');
});

test('dashboard destaca o botao de reservar quadra para a recepcao', function () {
    $recepcao = User::factory()->recepcao()->create();

    $this->actingAs($recepcao)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Reservar quadra')
        ->assertSee('Tablet do atleta');
});

test('foto facial enviada pelo whatsapp cai na pasta publica de faces do clube', function () {
    expect(config('filesystems.disks.public.root'))->toBe(public_path('storage'))
        ->and(config('filesystems.disks.public.url'))->toEndWith('/storage');

    $tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($tenant->id);
    $socio = User::factory()->create([
        'tenant_id' => $tenant->id,
        'cadastro_facial_completo' => false,
        'facial_token' => 'token-whatsapp-teste',
    ]);

    Livewire::test(CadastrarFacial::class, ['token' => 'token-whatsapp-teste'])
        ->set('foto', UploadedFile::fake()->image('rosto.jpg', 400, 400))
        ->call('salvar')
        ->assertSet('concluido', true);

    $caminho = (string) $socio->fresh()->face_photo_path;
    $arquivo = public_path('storage/'.$caminho);

    expect($caminho)->toStartWith('faces/'.$tenant->id.'/')
        ->and(is_file($arquivo))->toBeTrue();

    @unlink($arquivo);
});

test('pagina de ajuda abre para a recepcao e para o jogador mostra so o que serve', function () {
    $recepcao = User::factory()->recepcao()->create();

    $this->actingAs($recepcao)
        ->get(route('ajuda'))
        ->assertOk()
        ->assertSee('Primeiros passos do clube')
        ->assertSee('Recepção: reservar e acompanhar')
        ->assertSee('TV do clube');

    $jogador = User::factory()->create();

    $this->actingAs($jogador)
        ->get(route('ajuda'))
        ->assertOk()
        ->assertDontSee('Recepção: reservar e acompanhar')
        ->assertSee('Tablet do atleta');
});

test('painel mostra os primeiros passos quando falta cadastro e o tablet traz o passo a passo', function () {
    [$tenant, $admin] = clubeAoVivo();
    $tenant->update(['horario_funcionamento' => null]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Primeiros passos')
        ->assertSee('Cadastrar o horário de funcionamento');

    Livewire::actingAs($admin)
        ->test(ReservaTablet::class, ['modo' => 'secretaria'])
        ->assertSee('Como usar a recepção')
        ->assertSee('Saíram');
});
