<?php

declare(strict_types=1);

namespace App\Domains\Quadras\Livewire;

use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Quadras\Actions\SalvarQuadra;
use App\Domains\Quadras\Enums\QuadraStatus;
use App\Domains\Quadras\Enums\TipoPiso;
use App\Domains\Quadras\Models\Quadra;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class ListaQuadras extends Component
{
    use WithFileUploads;

    public bool $formAberto = false;

    public ?int $quadraId = null;

    public string $nome = '';

    public string $apelido = '';

    public string $tipo_piso = 'saibro';

    public bool $coberta = false;

    public bool $iluminacao = true;

    public string $status = 'disponivel';

    public int $ordem_exibicao = 1;

    public mixed $foto = null;

    public function mount(): void
    {
        abort_unless(in_array(auth()->user()?->role, [
            UserRole::Administrador,
            UserRole::Recepcao,
            UserRole::Professor,
        ], true), 403);

        $this->formAberto = auth()->user()->can('create', Quadra::class);
        if ($this->formAberto) {
            $this->ordem_exibicao = (int) Quadra::query()->max('ordem_exibicao') + 1;
        }
    }

    public function nova(): void
    {
        abort_unless(auth()->user()->can('create', Quadra::class), 403);
        $this->resetForm();
        $this->formAberto = true;
        $this->ordem_exibicao = (int) Quadra::query()->max('ordem_exibicao') + 1;
    }

    public function editar(int $id): void
    {
        $quadra = Quadra::query()->findOrFail($id);
        abort_unless(auth()->user()->can('update', $quadra), 403);

        $this->quadraId = $quadra->id;
        $this->nome = $quadra->nome;
        $this->apelido = (string) ($quadra->apelido ?? '');
        $this->tipo_piso = $quadra->tipo_piso->value;
        $this->coberta = $quadra->coberta;
        $this->iluminacao = $quadra->iluminacao;
        $this->status = $quadra->status->value;
        $this->ordem_exibicao = $quadra->ordem_exibicao;
        $this->foto = null;
        $this->formAberto = true;
    }

    public function salvar(SalvarQuadra $action): void
    {
        $quadra = $this->quadraId ? Quadra::query()->findOrFail($this->quadraId) : null;
        $foto = $this->foto instanceof UploadedFile ? $this->foto : null;

        $action->handle(auth()->user(), [
            'nome' => $this->nome,
            'apelido' => $this->apelido !== '' ? $this->apelido : null,
            'tipo_piso' => $this->tipo_piso,
            'coberta' => $this->coberta,
            'iluminacao' => $this->iluminacao,
            'status' => $this->status,
            'ordem_exibicao' => $this->ordem_exibicao,
        ], $foto, $quadra);

        $this->resetForm();
        session()->flash('status', 'Quadra salva.');
    }

    public function fechar(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        return view('domains.quadras.lista-quadras', [
            'quadras' => Quadra::query()->orderBy('ordem_exibicao')->get(),
            'pisos' => TipoPiso::cases(),
            'statusList' => QuadraStatus::cases(),
            'podeEditar' => auth()->user()->can('create', Quadra::class),
        ]);
    }

    private function resetForm(): void
    {
        $this->formAberto = auth()->user()?->can('create', Quadra::class) ?? false;
        $this->quadraId = null;
        $this->nome = '';
        $this->apelido = '';
        $this->tipo_piso = TipoPiso::Saibro->value;
        $this->coberta = false;
        $this->iluminacao = true;
        $this->status = QuadraStatus::Disponivel->value;
        $this->ordem_exibicao = 1;
        $this->foto = null;
        $this->resetValidation();
    }
}
