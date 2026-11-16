<?php

declare(strict_types=1);

namespace App\Domains\Jogadores\Livewire;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Clube\Services\LimitesDoClube;
use App\Domains\Jogadores\Actions\EnviarConviteAcesso;
use App\Domains\Jogadores\Actions\SalvarSocio;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Enums\UserStatus;
use App\Domains\Jogadores\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Equipe do clube (administrador, recepção e professor). Sempre do clube em que se está
 * navegando (para o master, o clube escolhido em "Entrar como admin").
 */
#[Layout('layouts.app')]
class ListaEquipe extends Component
{
    public bool $formAberto = false;

    public ?int $membroId = null;

    public string $nome = '';

    public string $email = '';

    public string $telefone = '';

    public string $role = 'recepcao';

    public function mount(): void
    {
        abort_unless(auth()->user()?->role === UserRole::Administrador, 403);
    }

    public function novo(): void
    {
        $this->resetForm();
        $this->formAberto = true;
    }

    public function editar(int $id): void
    {
        $membro = $this->membros()->findOrFail($id);
        abort_unless(auth()->user()->can('update', $membro), 403);

        $this->membroId = $membro->id;
        $this->nome = $membro->nome;
        $this->email = $membro->email;
        $this->telefone = (string) ($membro->telefone ?? '');
        $this->role = $membro->role->value;
        $this->formAberto = true;
        $this->resetValidation();
    }

    public function salvar(SalvarSocio $action): void
    {
        $this->validate([
            'role' => ['required', 'in:administrador,recepcao,professor'],
        ], [], ['role' => 'papel']);

        $membro = $this->membroId ? $this->membros()->findOrFail($this->membroId) : null;

        $action->handle(auth()->user(), [
            'nome' => $this->nome,
            'matricula' => $membro?->matricula ?? $this->proximaMatricula(),
            'telefone' => $this->telefone !== '' ? $this->telefone : null,
            'email' => $this->email,
            'role' => $this->role,
            'nivel' => null,
        ], null, null, $membro);

        $novo = $membro === null;
        $nome = $this->nome;
        $email = $this->email;
        $this->resetForm();

        session()->flash('status', $novo
            ? "{$nome} foi cadastrado(a). Enviamos um convite para {$email} criar a senha e entrar."
            : 'Dados da equipe atualizados.');
    }

    public function alternarAcesso(int $id): void
    {
        $membro = $this->membros()->findOrFail($id);
        abort_unless(auth()->user()->can('update', $membro), 403);

        if ($membro->id === auth()->id()) {
            session()->flash('erro', 'Você não pode bloquear o seu próprio acesso.');

            return;
        }

        $membro->forceFill(['bloqueado' => ! $membro->bloqueado])->save();

        session()->flash('status', $membro->bloqueado
            ? "{$membro->nome} não consegue mais entrar."
            : "{$membro->nome} voltou a ter acesso.");
    }

    public function reenviarConvite(int $id, EnviarConviteAcesso $convite): void
    {
        $membro = $this->membros()->findOrFail($id);
        abort_unless(auth()->user()->can('update', $membro), 403);

        if ($membro->status !== UserStatus::Pendente) {
            return;
        }

        $membro->forceFill([
            'confirmacao_token' => (string) Str::uuid(),
            'convite_enviado_em' => now(),
        ])->save();

        $convite->handle($membro->fresh());

        session()->flash('status', "Convite reenviado para {$membro->email}.");
    }

    public function fechar(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        $clube = Tenant::query()->find(auth()->user()->tenant_id);
        $limites = app(LimitesDoClube::class);

        return view('domains.jogadores.lista-equipe', [
            'membros' => $this->membros()->orderBy('nome')->get(),
            'clube' => $clube,
            'usada' => $limites->equipeUsada($clube),
            'limite' => $clube->limite_equipe,
            'temVaga' => $limites->temVaga($clube, UserRole::Recepcao),
        ]);
    }

    private function membros(): Builder
    {
        return User::query()
            ->where('super_admin', false)
            ->whereIn('role', [UserRole::Administrador->value, UserRole::Recepcao->value, UserRole::Professor->value]);
    }

    private function proximaMatricula(): string
    {
        $numero = User::query()->withTrashed()->where('matricula', 'like', 'EQ%')->count() + 1;

        do {
            $matricula = 'EQ'.str_pad((string) $numero, 3, '0', STR_PAD_LEFT);
            $numero++;
        } while (User::query()->withTrashed()->where('matricula', $matricula)->exists());

        return $matricula;
    }

    private function resetForm(): void
    {
        $this->formAberto = false;
        $this->membroId = null;
        $this->nome = '';
        $this->email = '';
        $this->telefone = '';
        $this->role = UserRole::Recepcao->value;
        $this->resetValidation();
    }
}
