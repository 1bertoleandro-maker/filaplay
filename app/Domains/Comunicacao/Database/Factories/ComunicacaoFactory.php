<?php

declare(strict_types=1);

namespace App\Domains\Comunicacao\Database\Factories;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Comunicacao\Enums\ComunicacaoCanal;
use App\Domains\Comunicacao\Enums\ComunicacaoStatus;
use App\Domains\Comunicacao\Models\Comunicacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comunicacao>
 */
class ComunicacaoFactory extends Factory
{
    protected $model = Comunicacao::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => null,
            'canal' => ComunicacaoCanal::Email,
            'destino' => fake()->safeEmail(),
            'assunto' => 'Aviso FILAPLAY',
            'corpo' => 'Mensagem de teste.',
            'status' => ComunicacaoStatus::Registrado,
            'enviado_em' => null,
        ];
    }
}
