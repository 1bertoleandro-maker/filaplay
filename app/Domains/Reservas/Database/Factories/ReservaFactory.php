<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Database\Factories;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Jogadores\Models\User;
use App\Domains\Quadras\Models\Quadra;
use App\Domains\Reservas\Enums\ReservaOrigem;
use App\Domains\Reservas\Enums\ReservaStatus;
use App\Domains\Reservas\Models\Reserva;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reserva>
 */
class ReservaFactory extends Factory
{
    protected $model = Reserva::class;

    public function definition(): array
    {
        $inicio = now()->addDay()->setTime(10, 0);

        return [
            'tenant_id' => Tenant::factory(),
            'quadra_id' => Quadra::factory(),
            'user_id' => User::factory(),
            'inicio' => $inicio,
            'fim' => $inicio->copy()->addHour(),
            'origem' => ReservaOrigem::Recepcao,
            'status' => ReservaStatus::Confirmada,
            'observacoes' => null,
        ];
    }
}
