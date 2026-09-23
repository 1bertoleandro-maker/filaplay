<?php

declare(strict_types=1);

namespace App\Domains\Quadras\Database\Seeders;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Quadras\Enums\QuadraStatus;
use App\Domains\Quadras\Enums\TipoPiso;
use App\Domains\Quadras\Models\Quadra;
use Illuminate\Database\Seeder;

class QuadraSeeder extends Seeder
{
    public function run(): void
    {
        $arena = Tenant::query()->where('email', 'contato@arena.demo.filaplay.com.br')->firstOrFail();
        $praia = Tenant::query()->where('email', 'contato@praia.demo.filaplay.com.br')->firstOrFail();

        $quadras = [
            ['Quadra 1', 'Central', TipoPiso::Saibro, true, true, 1],
            ['Quadra 2', 'Saibro', TipoPiso::Saibro, false, true, 2],
            ['Quadra 3', 'Beach', TipoPiso::Areia, false, true, 3],
            ['Quadra 4', 'Coberta', TipoPiso::Duro, true, true, 4],
        ];

        foreach ($quadras as [$nome, $apelido, $piso, $coberta, $luz, $ordem]) {
            Quadra::query()->updateOrCreate(
                ['tenant_id' => $arena->id, 'nome' => $nome],
                [
                    'apelido' => $apelido,
                    'tipo_piso' => $piso,
                    'coberta' => $coberta,
                    'iluminacao' => $luz,
                    'status' => QuadraStatus::Disponivel,
                    'ordem_exibicao' => $ordem,
                ],
            );
        }

        Quadra::query()->updateOrCreate(
            ['tenant_id' => $praia->id, 'nome' => 'Areia 1'],
            [
                'apelido' => 'Praia',
                'tipo_piso' => TipoPiso::Areia,
                'coberta' => false,
                'iluminacao' => true,
                'status' => QuadraStatus::Disponivel,
                'ordem_exibicao' => 1,
            ],
        );
    }
}
