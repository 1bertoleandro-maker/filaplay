<?php

declare(strict_types=1);

namespace App\Domains\Clube\Actions;

use App\Domains\Clube\Enums\TenantStatus;
use App\Domains\Clube\Models\Tenant;
use App\Domains\Jogadores\Actions\EnviarConviteAcesso;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Enums\UserStatus;
use App\Domains\Jogadores\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * O super admin cria um cliente (clube) direto, já ativo, e dá acesso a um
 * responsável. Diferente do autocadastro público, aqui não existe fila de
 * aprovação — o master já está aprovando ao criar. O responsável recebe um
 * convite por e-mail/WhatsApp para definir a própria senha.
 */
final class CriarClubePeloAdmin
{
    public function __construct(private readonly EnviarConviteAcesso $convite) {}

    /**
     * @param  array<string, mixed>  $dados
     */
    public function handle(User $master, array $dados): Tenant
    {
        abort_unless($master->super_admin, 403);

        $validados = Validator::make($dados, [
            'nome_clube' => ['required', 'string', 'max:120'],
            'nome_responsavel' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:tenants,email', 'unique:users,email'],
            'telefone' => ['nullable', 'string', 'max:32'],
            'expira_em' => ['nullable', 'date'],
        ])->validate();

        return DB::transaction(function () use ($validados, $master): Tenant {
            $tenant = Tenant::query()->create([
                'nome' => $validados['nome_clube'],
                'status' => TenantStatus::Ativo,
                'aprovado_em' => now(),
                'expira_em' => $validados['expira_em'] ?? null,
                'email' => $validados['email'],
                'telefone' => $validados['telefone'] ?? null,
                'raio_gps_metros' => 150,
            ]);

            $responsavel = User::query()->create([
                'tenant_id' => $tenant->id,
                'matricula' => 'ADM001',
                'nome' => $validados['nome_responsavel'],
                'telefone' => $validados['telefone'] ?? null,
                'email' => $validados['email'],
                'password' => Hash::make(Str::random(32)),
                'status' => UserStatus::Pendente,
                'bloqueado' => false,
                'cadastro_facial_completo' => false,
                'role' => UserRole::Administrador,
                'qr_token' => (string) Str::uuid(),
                'confirmacao_token' => (string) Str::uuid(),
                'convite_enviado_em' => now(),
            ]);

            $this->convite->handle(
                $responsavel,
                "Você foi cadastrado como responsável pelo {$tenant->nome} no FilaPlay pela equipe {$master->nome}.",
            );

            return $tenant;
        });
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function atualizarExpiracao(User $master, Tenant $tenant, array $dados): Tenant
    {
        abort_unless($master->super_admin, 403);

        $validados = Validator::make($dados, [
            'expira_em' => ['nullable', 'date'],
        ])->validate();

        $tenant->update(['expira_em' => $validados['expira_em'] ?? null]);

        return $tenant->fresh();
    }
}
