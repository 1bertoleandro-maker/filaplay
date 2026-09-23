<?php

declare(strict_types=1);

namespace App\Domains\Filas\Database\Factories;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Filas\Models\NoShow;
use App\Domains\Jogadores\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NoShow>
 */
class NoShowFactory extends Factory
{
    protected $model = NoShow::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'quadra_id' => null,
            'fila_id' => null,
            'registrado_em' => now(),
        ];
    }
}
