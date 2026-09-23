<?php

declare(strict_types=1);

namespace App\Domains\Clube\Database\Factories;

use App\Domains\Clube\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'nome' => fake()->company(),
            'endereco' => fake()->address(),
            'latitude' => fake()->latitude(-23.7, -23.4),
            'longitude' => fake()->longitude(-46.8, -46.4),
            'raio_gps_metros' => 150,
            'telefone' => fake()->numerify('(11) 9####-####'),
            'email' => fake()->unique()->companyEmail(),
            'horario_funcionamento' => [
                'segunda' => ['abre' => '07:00', 'fecha' => '22:00'],
                'terca' => ['abre' => '07:00', 'fecha' => '22:00'],
                'quarta' => ['abre' => '07:00', 'fecha' => '22:00'],
                'quinta' => ['abre' => '07:00', 'fecha' => '22:00'],
                'sexta' => ['abre' => '07:00', 'fecha' => '22:00'],
                'sabado' => ['abre' => '08:00', 'fecha' => '18:00'],
                'domingo' => ['abre' => '08:00', 'fecha' => '13:00'],
            ],
            'modo_chuva' => false,
            'exibir_logo' => true,
        ];
    }
}
