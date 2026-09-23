<?php

declare(strict_types=1);

namespace App\Domains\Filas\Database\Factories;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Filas\Enums\FilaStatus;
use App\Domains\Filas\Models\Fila;
use App\Domains\Jogadores\Models\User;
use App\Domains\Partidas\Enums\Modalidade;
use App\Domains\Quadras\Models\Quadra;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fila>
 */
class FilaFactory extends Factory
{
    protected $model = Fila::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'quadra_id' => Quadra::factory(),
            'user_id' => User::factory(),
            'modalidade' => Modalidade::Duplas,
            'prioridade' => 0,
            'posicao' => 1,
            'horario_entrada' => now(),
            'horario_estimado' => now()->addMinutes(30),
            'status' => FilaStatus::Aguardando,
        ];
    }
}
