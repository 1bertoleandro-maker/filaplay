<?php

declare(strict_types=1);

namespace App\Domains\Ranking\Database\Factories;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Jogadores\Models\User;
use App\Domains\Ranking\Models\Ranking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ranking>
 */
class RankingFactory extends Factory
{
    protected $model = Ranking::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'pontos' => 0,
            'vitorias' => 0,
            'partidas' => 0,
            'posicao' => 1,
            'atualizado_em' => now(),
        ];
    }
}
