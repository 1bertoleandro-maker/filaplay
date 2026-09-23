<?php

namespace Database\Seeders;

use App\Domains\Clube\Database\Seeders\TenantSeeder;
use App\Domains\Configuracoes\Database\Seeders\ConfiguracaoSeeder;
use App\Domains\Jogadores\Database\Seeders\UserSeeder;
use App\Domains\Quadras\Database\Seeders\QuadraSeeder;
use Illuminate\Database\Seeder;

/**
 * Dados de demonstração (clube fake, sócios, quadras). NÃO é chamado pelo
 * DatabaseSeeder padrão — a base de produção deve nascer limpa, só com o
 * super admin. Use manualmente em ambiente local, se precisar de dados de exemplo:
 *   php artisan db:seed --class="Database\Seeders\DemoSeeder"
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TenantSeeder::class,
            UserSeeder::class,
            QuadraSeeder::class,
            ConfiguracaoSeeder::class,
        ]);
    }
}
