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
 * Cadastro público de um novo clube (tenant). O clube nasce ativo, em teste grátis
 * (config filaplay.dias_teste) e com limite de usuários de equipe; o master pode
 * ajustar plano, validade e limites em "Clubes da plataforma".
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
                // Teste grátis: já entra direto; depois do prazo precisa contratar.
                'status' => TenantStatus::Ativo,
                'aprovado_em' => now(),
                'teste_gratis' => true,
                'expira_em' => now()->addDays((int) config('filaplay.dias_teste'))->endOfDay(),
                'limite_equipe' => (int) config('filaplay.equipe_padrao'),
                'limite_socios' => null,
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
                corpo: 'Novo clube em teste grátis de '.config('filaplay.dias_teste')." dias: {$tenant->nome} ({$validados['email']}). Veja e ajuste o plano em /admin/clubes.",
                assunto: 'Novo clube em teste no FilaPlay',
            );

            return $tenant;
        });
    }
}
