<?php

declare(strict_types=1);

namespace App\Domains\Configuracoes\Database\Seeders;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Configuracoes\ConfiguracaoChave;
use App\Domains\Configuracoes\Models\Configuracao;
use Illuminate\Database\Seeder;

class ConfiguracaoSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()->each(function (Tenant $tenant): void {
            foreach (ConfiguracaoChave::padroes() as $chave => $valor) {
                Configuracao::query()->updateOrCreate(
                    ['tenant_id' => $tenant->id, 'chave' => $chave],
                    ['valor' => $valor],
                );
            }
        });
    }
}
