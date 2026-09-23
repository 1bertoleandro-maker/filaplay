<?php

declare(strict_types=1);

namespace App\Domains\Partidas\Database\Factories;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Partidas\Enums\Modalidade;
use App\Domains\Partidas\Enums\PartidaStatus;
use App\Domains\Partidas\Models\Partida;
use App\Domains\Quadras\Models\Quadra;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partida>
 */
class PartidaFactory extends Factory
{
    protected $model = Partida::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'quadra_id' => Quadra::factory(),
            'modalidade' => Modalidade::Simples,
            'duracao_minutos' => 60,
            'inicio_previsto' => now()->addHour(),
            'inicio_real' => null,
            'fim_real' => null,
            'tempo_extra' => 0,
            'status' => PartidaStatus::Agendada,
        ];
    }
}
