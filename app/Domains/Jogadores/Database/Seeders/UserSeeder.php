<?php

declare(strict_types=1);

namespace App\Domains\Jogadores\Database\Seeders;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Jogadores\Enums\NivelJogador;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Enums\UserStatus;
use App\Domains\Jogadores\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $arena = Tenant::query()->where('email', 'contato@arena.demo.filaplay.com.br')->firstOrFail();
        $praia = Tenant::query()->where('email', 'contato@praia.demo.filaplay.com.br')->firstOrFail();

        $this->usuario($arena->id, 'ADM001', 'Ana Administradora', 'admin@arena.demo.filaplay.com.br', UserRole::Administrador, null);
        $this->usuario($arena->id, 'REC001', 'Rita Recepção', 'recepcao@arena.demo.filaplay.com.br', UserRole::Recepcao, null);
        $this->usuario($arena->id, 'PRO001', 'Paulo Professor', 'professor@arena.demo.filaplay.com.br', UserRole::Professor, NivelJogador::Profissional);

        $jogadores = [
            ['JOG001', 'Carlos Silva', NivelJogador::Iniciante],
            ['JOG002', 'Marina Costa', NivelJogador::Intermediario],
            ['JOG003', 'João Pereira', NivelJogador::Avancado],
            ['JOG004', 'Helena Dias', NivelJogador::Intermediario],
            ['JOG005', 'Lucas Almeida', NivelJogador::Iniciante],
            ['JOG006', 'Beatriz Nunes', NivelJogador::Avancado],
            ['JOG007', 'Rafael Gomes', NivelJogador::Profissional],
            ['JOG008', 'Camila Rocha', NivelJogador::Intermediario],
        ];

        foreach ($jogadores as [$matricula, $nome, $nivel]) {
            $email = strtolower($matricula).'@arena.demo.filaplay.com.br';
            $this->usuario($arena->id, $matricula, $nome, $email, UserRole::Jogador, $nivel);
        }

        $this->usuario($praia->id, 'ADM001', 'Bruno Praia', 'admin@praia.demo.filaplay.com.br', UserRole::Administrador, null);
    }

    private function usuario(
        int $tenantId,
        string $matricula,
        string $nome,
        string $email,
        UserRole $role,
        ?NivelJogador $nivel,
    ): void {
        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'tenant_id' => $tenantId,
                'matricula' => $matricula,
                'nome' => $nome,
                'telefone' => '(11) 90000-0000',
                'password' => 'password',
                'email_verified_at' => now(),
                'status' => UserStatus::Ativo,
                'nivel' => $nivel,
                'bloqueado' => false,
                'cadastro_facial_completo' => false,
                'role' => $role,
                'qr_token' => (string) Str::uuid(),
            ],
        );
    }
}
