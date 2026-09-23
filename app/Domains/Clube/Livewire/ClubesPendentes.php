<?php

declare(strict_types=1);

namespace App\Domains\Clube\Livewire;

use App\Domains\Clube\Actions\AprovarClube;
use App\Domains\Clube\Actions\CriarClubePeloAdmin;
use App\Domains\Clube\Enums\TenantStatus;
use App\Domains\Clube\Models\Tenant;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ClubesPendentes extends Component
{
    public bool $formAberto = false;

    public string $nome_clube = '';

    public string $nome_responsavel = '';

    public string $email = '';

    public string $telefone = '';

    public string $expira_em = '';

    public ?int $editandoExpiracaoId = null;

    public string $novaExpiracao = '';

    public function mount(): void
    {
        abort_unless((bool) auth()->user()?->super_admin, 403);
    }

    public function novoCliente(): void
    {
        $this->reset(['nome_clube', 'nome_responsavel', 'email', 'telefone', 'expira_em']);
        $this->resetValidation();
        $this->formAberto = true;
    }

    public function criar(CriarClubePeloAdmin $action): void
    {
        $action->handle(auth()->user(), [
            'nome_clube' => $this->nome_clube,
            'nome_responsavel' => $this->nome_responsavel,
            'email' => $this->email,
            'telefone' => $this->telefone !== '' ? $this->telefone : null,
            'expira_em' => $this->expira_em !== '' ? $this->expira_em : null,
        ]);

        $this->formAberto = false;
        session()->flash('status', "Cliente criado! Enviamos um convite de acesso para {$this->email}.");
    }

    public function fechar(): void
    {
        $this->formAberto = false;
        $this->resetValidation();
    }

    public function editarExpiracao(int $tenantId): void
    {
        $tenant = Tenant::query()->findOrFail($tenantId);
        $this->editandoExpiracaoId = $tenantId;
        $this->novaExpiracao = $tenant->expira_em?->format('Y-m-d') ?? '';
    }

    public function salvarExpiracao(CriarClubePeloAdmin $action): void
    {
        $tenant = Tenant::query()->findOrFail($this->editandoExpiracaoId);

        $action->atualizarExpiracao(auth()->user(), $tenant, [
            'expira_em' => $this->novaExpiracao !== '' ? $this->novaExpiracao : null,
        ]);

        $this->editandoExpiracaoId = null;
        session()->flash('status', 'Validade atualizada.');
    }

    public function cancelarEdicaoExpiracao(): void
    {
        $this->editandoExpiracaoId = null;
    }

    public function aprovar(AprovarClube $action, int $tenantId): void
    {
        $tenant = Tenant::query()->findOrFail($tenantId);
        $action->aprovar(auth()->user(), $tenant);
        session()->flash('status', 'Clube aprovado. O responsável já consegue entrar.');
    }

    public function bloquear(AprovarClube $action, int $tenantId): void
    {
        $tenant = Tenant::query()->findOrFail($tenantId);
        $action->bloquear(auth()->user(), $tenant);
        session()->flash('status', 'Clube bloqueado.');
    }

    public function render(): View
    {
        return view('domains.clube.clubes-pendentes', [
            'clubes' => Tenant::query()->orderByDesc('created_at')->get(),
            'statusEnum' => TenantStatus::class,
        ]);
    }
}
