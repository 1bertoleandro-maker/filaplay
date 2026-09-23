<?php

namespace Database\Seeders;

use App\Domains\Clube\Database\Seeders\SuperAdminSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Base de produção: nasce limpa, só com o super admin da FilaPlay
     * (leandro.queiroz@qisolution.com.br). Todo o resto — clubes, sócios, quadras —
     * é criado pelo próprio cliente depois que o master libera o acesso dele.
     *
     * Dados de demonstração ficam em DemoSeeder (opcional, uso local).
     */
    public function run(): void
    {
        $this->call([
            SuperAdminSeeder::class,
        ]);
    }
}
