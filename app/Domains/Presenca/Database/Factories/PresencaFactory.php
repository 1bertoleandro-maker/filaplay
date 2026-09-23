<?php

declare(strict_types=1);

namespace App\Domains\Presenca\Database\Factories;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Jogadores\Models\User;
use App\Domains\Presenca\Enums\PresencaMetodo;
use App\Domains\Presenca\Models\Presenca;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Presenca>
 */
class PresencaFactory extends Factory
{
    protected $model = Presenca::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'metodo' => PresencaMetodo::Tablet,
            'latitude' => null,
            'longitude' => null,
            'validado_em' => now(),
        ];
    }
}
