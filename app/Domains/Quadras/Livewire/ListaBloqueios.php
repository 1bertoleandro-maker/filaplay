<?php

declare(strict_types=1);

namespace App\Domains\Quadras\Livewire;

use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Quadras\Actions\SalvarBloqueio;
use App\Domains\Quadras\Enums\BloqueioTipo;
use App\Domains\Quadras\Models\Bloqueio;
use App\Domains\Quadras\Models\Quadra;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ListaBloqueios extends Component
{
    public bool $formAberto = false;

    public ?int $quadra_id = null;

    public string $tipo = 'manutencao';

    public string $data = '';

    public string $hora_inicio = '08:00';

    public string $hora_fim = '10:00';

    public string $motivo = '';

    public bool $recorrente = false;

    public int $semanas = 4;

    public function mount(): void
    {
        abort_unless(in_array(auth()->user()?->role, [
            UserRole::Administrador,
            UserRole::Recepcao,
        ], true), 403);

        $this->data = now()->toDateString();
        $this->formAberto = true;
    }

    public function nova(): void
    {
        $this->formAberto = true;
        $this->quadra_id = Quadra::query()->orderBy('ordem_exibicao')->value('id');
        $this->tipo = 'manutencao';
        $this->data = now()->toDateString();
        $this->hora_inicio = '08:00';
        $this->hora_fim = '10:00';
        $this->motivo = '';
        $this->recorrente = false;
        $this->semanas = 4;
        $this->resetValidation();
    }

    public function salvar(SalvarBloqueio $action): void
    {
        $this->validate([
            'quadra_id' => ['required', 'integer'],
            'tipo' => ['required', 'string'],
            'data' => ['required', 'date'],
            'hora_inicio' => ['required'],
            'hora_fim' => ['required'],
            'motivo' => ['required', 'string', 'max:255'],
        ]);

        $action->handle(
            (int) auth()->user()->tenant_id,
            (int) $this->quadra_id,
            BloqueioTipo::from($this->tipo),
            $this->data,
            $this->hora_inicio,
            $this->hora_fim,
            $this->motivo,
            $this->recorrente,
            $this->semanas,
        );

        $this->formAberto = false;
        session()->flash('status', 'Bloqueio cadastrado.');
    }

    public function remover(int $id): void
    {
        $bloqueio = Bloqueio::query()->find($id);
        $bloqueio?->delete();
    }

    public function fechar(): void
    {
        $this->formAberto = false;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('domains.quadras.lista-bloqueios', [
            'bloqueios' => Bloqueio::query()
                ->with('quadra')
                ->where('fim', '>=', now())
                ->orderBy('inicio')
                ->limit(40)
                ->get(),
            'quadras' => Quadra::query()->orderBy('ordem_exibicao')->get(),
            'tipos' => BloqueioTipo::cases(),
        ]);
    }
}
