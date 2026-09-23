<?php

declare(strict_types=1);

namespace App\Domains\Quadras\Database\Factories;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Quadras\Enums\BloqueioTipo;
use App\Domains\Quadras\Models\Bloqueio;
use App\Domains\Quadras\Models\Quadra;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bloqueio>
 */
class BloqueioFactory extends Factory
{
    protected $model = Bloqueio::class;

    public function definition(): array
    {
        $inicio = now()->addDay()->setTime(8, 0);

        return [
            'tenant_id' => Tenant::factory(),
            'quadra_id' => Quadra::factory(),
            'tipo' => BloqueioTipo::Manutencao,
            'inicio' => $inicio,
            'fim' => $inicio->copy()->addHours(2),
            'motivo' => 'Manutenção programada',
        ];
    }
}
