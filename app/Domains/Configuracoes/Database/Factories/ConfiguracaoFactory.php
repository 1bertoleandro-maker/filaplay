<?php

declare(strict_types=1);

namespace App\Domains\Configuracoes\Database\Factories;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Configuracoes\Models\Configuracao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Configuracao>
 */
class ConfiguracaoFactory extends Factory
{
    protected $model = Configuracao::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'chave' => 'modalidade.simples.duracao_minutos',
            'valor' => ['minutos' => 60],
        ];
    }
}
