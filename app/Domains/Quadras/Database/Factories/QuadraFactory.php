<?php

declare(strict_types=1);

namespace App\Domains\Quadras\Database\Factories;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Quadras\Enums\QuadraStatus;
use App\Domains\Quadras\Enums\TipoPiso;
use App\Domains\Quadras\Models\Quadra;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quadra>
 */
class QuadraFactory extends Factory
{
    protected $model = Quadra::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'nome' => 'Quadra '.fake()->unique()->numberBetween(1, 100000),
            'apelido' => fake()->randomElement(['Central', 'Saibro', 'Beach', 'Coberta']),
            'tipo_piso' => TipoPiso::Saibro,
            'coberta' => false,
            'iluminacao' => true,
            'status' => QuadraStatus::Disponivel,
            'ordem_exibicao' => fake()->numberBetween(1, 12),
        ];
    }
}
