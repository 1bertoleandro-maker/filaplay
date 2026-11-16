<?php

declare(strict_types=1);

namespace App\Domains\Clube\Livewire;

use App\Domains\Clube\Actions\AprovarClube;
use App\Domains\Clube\Actions\CriarClubePeloAdmin;
use App\Domains\Clube\Enums\TenantStatus;
use App\Domains\Clube\Models\Tenant;
use App\Domains\Clube\Services\LimitesDoClube;
use App\Http\Middleware\SetTenant;
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

    /** Plano do clube novo criado pelo master. */
    public bool $teste_gratis = false;

    public string $limite_equipe = '';

    public string $limite_socios = '';

    public ?int $editandoExpiracaoId = null;

    public string $novaExpiracao = '';

    public bool $novoTeste = false;

    public string $novoLimiteEquipe = '';

    public string $novoLimiteSocios = '';

    public function mount(): void
    {
        abort_unless((bool) auth()->user()?->super_admin, 403);
    }

    public function novoCliente(): void
    {
        $this->reset(['nome_clube', 'nome_responsavel', 'email', 'telefone', 'expira_em', 'teste_gratis', 'limite_socios']);
        $this->limite_equipe = (string) config('filaplay.equipe_padrao');
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
            'teste_gratis' => $this->teste_gratis,
            'limite_equipe' => $this->limite_equipe !== '' ? $this->limite_equipe : null,
            'limite_socios' => $this->limite_socios !== '' ? $this->limite_socios : null,
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
        $this->novoTeste = (bool) $tenant->teste_gratis;
        $this->novoLimiteEquipe = (string) ($tenant->limite_equipe ?? '');
        $this->novoLimiteSocios = (string) ($tenant->limite_socios ?? '');
        $this->resetValidation();
    }

    public function salvarExpiracao(CriarClubePeloAdmin $action): void
    {
        $tenant = Tenant::query()->findOrFail($this->editandoExpiracaoId);

        $action->atualizarExpiracao(auth()->user(), $tenant, [
            'expira_em' => $this->novaExpiracao !== '' ? $this->novaExpiracao : null,
            'teste_gratis' => $this->novoTeste,
            'limite_equipe' => $this->novoLimiteEquipe !== '' ? $this->novoLimiteEquipe : null,
            'limite_socios' => $this->novoLimiteSocios !== '' ? $this->novoLimiteSocios : null,
        ]);

        $this->editandoExpiracaoId = null;
        session()->flash('status', 'Plano do clube atualizado.');
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
        $clubes = Tenant::query()->orderByDesc('created_at')->get();
        $limites = app(LimitesDoClube::class);

        $uso = $clubes->mapWithKeys(fn (Tenant $clube): array => [$clube->id => $limites->resumo($clube)]);

        $resumo = [
            'clubes' => $clubes->count(),
            'em_teste' => $clubes->filter(fn (Tenant $c): bool => $c->emTeste())->count(),
            'pagantes' => $clubes->filter(fn (Tenant $c): bool => ! $c->teste_gratis && $c->status === TenantStatus::Ativo && ! $c->expirado())->count(),
            'vencidos' => $clubes->filter(fn (Tenant $c): bool => $c->expirado() || $c->status === TenantStatus::Bloqueado)->count(),
            'socios' => $uso->sum('socios_usados'),
            'equipe' => $uso->sum('equipe_usada'),
        ];

        return view('domains.clube.clubes-pendentes', [
            'clubes' => $clubes,
            'uso' => $uso,
            'resumo' => $resumo,
            'statusEnum' => TenantStatus::class,
            'clubeAtivoId' => session(SetTenant::CLUBE_ATIVO),
        ]);
    }
}
