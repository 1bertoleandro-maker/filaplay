<?php

declare(strict_types=1);

namespace App\Domains\Clube\Actions;

use App\Domains\Clube\Enums\TenantStatus;
use App\Domains\Clube\Models\Tenant;
use App\Domains\Comunicacao\Contracts\ClubNotifier;
use App\Domains\Comunicacao\Enums\ComunicacaoCanal;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Enums\UserStatus;
use App\Domains\Jogadores\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Cadastro público de um novo clube (tenant). O clube nasce "pendente" e só
 * o super admin da FilaPlay pode liberar o acesso (ver AprovarClube).
 */
final class CadastrarNovoClube
{
    public function __construct(private readonly ClubNotifier $notifier) {}

    /**
     * @param  array<string, mixed>  $dados
     */
    public function handle(array $dados, ?string $googleId = null): Tenant
    {
        $regras = [
            'nome_clube' => ['required', 'string', 'max:120'],
            'nome_responsavel' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:tenants,email', 'unique:users,email'],
            'telefone' => ['nullable', 'string', 'max:32'],
        ];

        if ($googleId === null) {
            $regras['senha'] = ['required', 'string', 'min:8'];
        }

        $validados = Validator::make($dados, $regras)->validate();

        return DB::transaction(function () use ($validados, $googleId): Tenant {
            $tenant = Tenant::query()->create([
                'nome' => $validados['nome_clube'],
                'status' => TenantStatus::Pendente,
                'email' => $validados['email'],
                'telefone' => $validados['telefone'] ?? null,
                'raio_gps_metros' => 150,
            ]);

            User::query()->create([
                'tenant_id' => $tenant->id,
                'matricula' => 'ADM001',
                'nome' => $validados['nome_responsavel'],
                'telefone' => $validados['telefone'] ?? null,
                'email' => $validados['email'],
                'google_id' => $googleId,
                'password' => $googleId !== null ? Hash::make(Str::random(32)) : $validados['senha'],
                'email_verified_at' => $googleId !== null ? now() : null,
                'status' => UserStatus::Ativo,
                'bloqueado' => false,
                'cadastro_facial_completo' => false,
                'role' => UserRole::Administrador,
                'qr_token' => (string) Str::uuid(),
            ]);

            $this->notifier->send(
                tenantId: $tenant->id,
                canal: ComunicacaoCanal::Email,
                destino: 'leandro.queiroz@qisolution.com.br',
                corpo: "Novo clube aguardando aprovação: {$tenant->nome} ({$validados['email']}). Acesse /admin/clubes para liberar.",
                assunto: 'Novo clube cadastrado no FilaPlay',
            );

            return $tenant;
        });
    }
}
