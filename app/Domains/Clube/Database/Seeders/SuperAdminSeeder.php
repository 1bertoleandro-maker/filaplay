<?php

declare(strict_types=1);

namespace App\Domains\Clube\Database\Seeders;

use App\Domains\Clube\Enums\TenantStatus;
use App\Domains\Clube\Models\Tenant;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Enums\UserStatus;
use App\Domains\Jogadores\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Cria o clube interno da FilaPlay e os super admins da ferramenta: só eles
 * aprovam/criam novos clubes (tenants) em /admin/clubes.
 */
class SuperAdminSeeder extends Seeder
{
    /** @var array<int, array{email: string, nome: string, matricula: string}> */
    private const SUPER_ADMINS = [
        ['email' => 'leandro.queiroz@qisolution.com.br', 'nome' => 'Leandro Queiroz', 'matricula' => 'SUPERADMIN'],
    ];

    public function run(): void
    {
        $hq = Tenant::query()->updateOrCreate(
            ['email' => 'hq@filaplay.com.br'],
            [
                'nome' => 'FilaPlay (equipe interna)',
                'status' => TenantStatus::Ativo,
                'aprovado_em' => now(),
                'raio_gps_metros' => 150,
                'modo_chuva' => false,
            ],
        );

        foreach (self::SUPER_ADMINS as $admin) {
            User::query()->updateOrCreate(
                ['email' => $admin['email']],
                [
                    'tenant_id' => $hq->id,
                    'matricula' => $admin['matricula'],
                    'nome' => $admin['nome'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'status' => UserStatus::Ativo,
                    'bloqueado' => false,
                    'cadastro_facial_completo' => false,
                    'role' => UserRole::Administrador,
                    'super_admin' => true,
                    'qr_token' => (string) Str::uuid(),
                ],
            );
        }
    }
}
