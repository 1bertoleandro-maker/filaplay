<?php

declare(strict_types=1);

namespace App\Domains\Clube\Database\Seeders;

use App\Domains\Clube\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $horario = [
            'segunda' => ['abre' => '07:00', 'fecha' => '22:00'],
            'terca' => ['abre' => '07:00', 'fecha' => '22:00'],
            'quarta' => ['abre' => '07:00', 'fecha' => '22:00'],
            'quinta' => ['abre' => '07:00', 'fecha' => '22:00'],
            'sexta' => ['abre' => '07:00', 'fecha' => '22:00'],
            'sabado' => ['abre' => '08:00', 'fecha' => '18:00'],
            'domingo' => ['abre' => '08:00', 'fecha' => '13:00'],
        ];

        Tenant::query()->updateOrCreate(
            ['email' => 'contato@arena.demo.filaplay.com.br'],
            [
                'nome' => 'Arena GoTreino',
                'endereco' => 'Rua das Quadras, 120 - São Paulo/SP',
                'latitude' => -23.5614140,
                'longitude' => -46.6558810,
                'raio_gps_metros' => 150,
                'telefone' => '(11) 98888-1000',
                'horario_funcionamento' => $horario,
                'modo_chuva' => false,
            ],
        );

        Tenant::query()->updateOrCreate(
            ['email' => 'contato@praia.demo.filaplay.com.br'],
            [
                'nome' => 'Clube Praia Sul',
                'endereco' => 'Av. Beira Mar, 45 - Santos/SP',
                'latitude' => -23.9671420,
                'longitude' => -46.3282530,
                'raio_gps_metros' => 200,
                'telefone' => '(13) 97777-2000',
                'horario_funcionamento' => $horario,
                'modo_chuva' => false,
            ],
        );
    }
}
