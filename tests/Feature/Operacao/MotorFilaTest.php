<?php

declare(strict_types=1);

use App\Domains\Clube\Models\Tenant;
use App\Domains\Filas\Enums\FilaStatus;
use App\Domains\Filas\Models\Fila;
use App\Domains\Filas\Models\NoShow;
use App\Domains\Filas\Services\FilaEngine;
use App\Domains\Jogadores\Models\User;
use App\Domains\Partidas\Enums\Modalidade;
use App\Domains\Partidas\Enums\PartidaStatus;
use App\Domains\Partidas\Models\Partida;
use App\Domains\Presenca\Actions\RegistrarPresenca;
use App\Domains\Presenca\Models\Presenca;
use App\Domains\Quadras\Enums\QuadraStatus;
use App\Domains\Quadras\Models\Bloqueio;
use App\Domains\Quadras\Models\Quadra;
use App\Support\Tenancy\TenantContext;
use Illuminate\Validation\ValidationException;

function clubeParaFila(bool $coberta = true): array
{
    $tenant = Tenant::factory()->create([
        'latitude' => -23.5505,
        'longitude' => -46.6333,
        'raio_gps_metros' => 100,
    ]);
    app(TenantContext::class)->set($tenant->id);

    $quadra = Quadra::factory()->create(['tenant_id' => $tenant->id, 'coberta' => $coberta]);

    return [$tenant->fresh(), $quadra->fresh()];
}

function socioComPresenca(Tenant $tenant): User
{
    $socio = User::factory()->create(['tenant_id' => $tenant->id]);
    Presenca::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $socio->id,
        'metodo' => 'tablet',
        'validado_em' => now(),
    ]);

    return $socio->fresh();
}

test('fila chama os proximos apenas quando ha jogadores suficientes e inicia a partida ao confirmar todos', function () {
    [$tenant, $quadra] = clubeParaFila();
    $engine = app(FilaEngine::class);

    $jogador1 = socioComPresenca($tenant);
    $jogador2 = socioComPresenca($tenant);

    $engine->entrar($quadra, $jogador1, Modalidade::Simples);

    expect(Fila::query()->where('user_id', $jogador1->id)->first()->status)->toBe(FilaStatus::Aguardando);

    $engine->entrar($quadra, $jogador2, Modalidade::Simples);

    expect(Fila::query()->where('user_id', $jogador1->id)->first()->status)->toBe(FilaStatus::Chamado)
        ->and(Fila::query()->where('user_id', $jogador2->id)->first()->status)->toBe(FilaStatus::Chamado);

    $engine->confirmarChamada($jogador1);
    expect(Partida::query()->count())->toBe(0);

    $engine->confirmarChamada($jogador2);

    $partida = Partida::query()->first();
    expect($partida)->not->toBeNull()
        ->and($partida->status)->toBe(PartidaStatus::EmAndamento)
        ->and($partida->duracao_minutos)->toBe(60)
        ->and($partida->jogadores()->count())->toBe(2)
        ->and($quadra->fresh()->status)->toBe(QuadraStatus::Ocupada);

    $engine->adicionarTempoExtra($partida, 5);
    expect($partida->fresh()->tempo_extra)->toBe(5);

    $engine->encerrarPartida($partida->fresh());

    expect($partida->fresh()->status)->toBe(PartidaStatus::Encerrada)
        ->and($quadra->fresh()->status)->toBe(QuadraStatus::Disponivel)
        ->and(Fila::query()->where('user_id', $jogador1->id)->first()->status)->toBe(FilaStatus::Removido);
});

test('jogador nao pode entrar em duas filas nem sem presenca validada', function () {
    [$tenant, $quadra] = clubeParaFila();
    $engine = app(FilaEngine::class);

    $semPresenca = User::factory()->create(['tenant_id' => $tenant->id]);

    expect(fn () => $engine->entrar($quadra, $semPresenca, Modalidade::Simples))
        ->toThrow(ValidationException::class);

    $jogador = socioComPresenca($tenant);
    $engine->entrar($quadra, $jogador, Modalidade::Simples);

    $outraQuadra = Quadra::factory()->create(['tenant_id' => $tenant->id]);

    expect(fn () => $engine->entrar($outraQuadra, $jogador, Modalidade::Simples))
        ->toThrow(ValidationException::class);
});

test('bloqueio ativo impede entrada e chamada na quadra', function () {
    [$tenant, $quadra] = clubeParaFila();
    $engine = app(FilaEngine::class);

    Bloqueio::query()->create([
        'tenant_id' => $tenant->id,
        'quadra_id' => $quadra->id,
        'tipo' => 'manutencao',
        'inicio' => now()->subMinutes(5),
        'fim' => now()->addHour(),
        'motivo' => 'Manutenção do piso',
    ]);

    $jogador = socioComPresenca($tenant);

    expect(fn () => $engine->entrar($quadra, $jogador, Modalidade::Simples))
        ->toThrow(ValidationException::class);
});

test('modo chuva bloqueia quadra descoberta e reduz duracao da partida', function () {
    [$tenant, $quadraDescoberta] = clubeParaFila(coberta: false);
    $quadraCoberta = Quadra::factory()->create(['tenant_id' => $tenant->id, 'coberta' => true]);
    $engine = app(FilaEngine::class);

    $engine->alternarModoChuva($tenant, true);

    $jogador = socioComPresenca($tenant);

    expect(fn () => $engine->entrar($quadraDescoberta, $jogador, Modalidade::Simples))
        ->toThrow(ValidationException::class);

    $j1 = socioComPresenca($tenant);
    $j2 = socioComPresenca($tenant);
    $engine->entrar($quadraCoberta, $j1, Modalidade::Simples);
    $engine->entrar($quadraCoberta, $j2, Modalidade::Simples);
    $engine->confirmarChamada($j1);
    $engine->confirmarChamada($j2);

    expect(Partida::query()->first()->duracao_minutos)->toBe(40);
});

test('no-show automatico libera o lugar e chama o proximo da fila', function () {
    [$tenant, $quadra] = clubeParaFila();
    $engine = app(FilaEngine::class);

    $j1 = socioComPresenca($tenant);
    $j2 = socioComPresenca($tenant);
    $j3 = socioComPresenca($tenant);

    $engine->entrar($quadra, $j1, Modalidade::Simples);
    $engine->entrar($quadra, $j2, Modalidade::Simples);
    $engine->entrar($quadra, $j3, Modalidade::Simples);

    Fila::query()->where('user_id', $j1->id)->update(['confirmar_at' => now()->subMinute()]);
    Fila::query()->where('user_id', $j2->id)->update(['confirmar_at' => now()->subMinute()]);

    $engine->processarNoShows($tenant->fresh());

    expect(Fila::query()->where('user_id', $j1->id)->first()->status)->toBe(FilaStatus::NoShow)
        ->and(NoShow::query()->count())->toBe(2)
        // Simples exige 2 jogadores: com apenas j3 restando, ele fica aguardando novos jogadores.
        ->and(Fila::query()->where('user_id', $j3->id)->first()->status)->toBe(FilaStatus::Aguardando);
});

test('presenca por gps exige estar dentro do raio do clube', function () {
    [$tenant, $quadra] = clubeParaFila();
    $jogador = User::factory()->create(['tenant_id' => $tenant->id]);
    $action = app(RegistrarPresenca::class);

    expect(fn () => $action->porGps($jogador, -23.7, -46.9))
        ->toThrow(ValidationException::class);

    $action->porGps($jogador, -23.5506, -46.6334);

    expect(Presenca::query()->where('user_id', $jogador->id)->count())->toBe(1);
});
