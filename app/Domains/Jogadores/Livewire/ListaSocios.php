<?php

declare(strict_types=1);

namespace App\Domains\Jogadores\Livewire;

use App\Domains\Jogadores\Actions\ImportarSociosPlanilha;
use App\Domains\Jogadores\Actions\SalvarSocio;
use App\Domains\Jogadores\Enums\NivelJogador;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ListaSocios extends Component
{
    use WithFileUploads;
    use WithPagination;

    public bool $formAberto = true;

    public bool $importarAberto = false;

    public mixed $planilha = null;

    /** @var array{criados:int, linhas:int, erros: array<int,string>}|null */
    public ?array $resultadoImportacao = null;

    public ?int $socioId = null;

    public string $nome = '';

    public string $matricula = '';

    public string $telefone = '';

    public string $email = '';

    public string $role = 'jogador';

    public string $nivel = 'intermediario';

    public mixed $foto = null;

    public mixed $face = null;

    public string $busca = '';

    public function mount(): void
    {
        abort_unless(in_array(auth()->user()?->role, [
            UserRole::Administrador,
            UserRole::Recepcao,
        ], true), 403);
    }

    public function updatedBusca(): void
    {
        $this->resetPage();
    }

    public function novo(): void
    {
        abort_unless(auth()->user()->can('create', User::class), 403);
        $this->resetForm();
        $this->formAberto = true;
    }

    public function editar(int $id): void
    {
        $socio = User::query()->findOrFail($id);
        abort_unless(auth()->user()->can('update', $socio), 403);

        $this->socioId = $socio->id;
        $this->nome = $socio->nome;
        $this->matricula = $socio->matricula;
        $this->telefone = (string) ($socio->telefone ?? '');
        $this->email = $socio->email;
        $this->role = $socio->role->value;
        $this->nivel = $socio->nivel?->value ?? '';
        $this->foto = null;
        $this->face = null;
        $this->formAberto = true;
    }

    public function salvar(SalvarSocio $action): void
    {
        $socio = $this->socioId ? User::query()->findOrFail($this->socioId) : null;
        $foto = $this->foto instanceof UploadedFile ? $this->foto : null;
        $face = $this->face instanceof UploadedFile ? $this->face : null;

        $action->handle(auth()->user(), [
            'nome' => $this->nome,
            'matricula' => $this->matricula,
            'telefone' => $this->telefone !== '' ? $this->telefone : null,
            'email' => $this->email,
            'role' => $this->role,
            'nivel' => $this->nivel !== '' ? $this->nivel : null,
        ], $foto, $face, $socio);

        $this->resetForm();
        session()->flash('status', 'Sócio salvo.');
    }

    public function fechar(): void
    {
        $this->resetForm();
    }

    public function abrirImportacao(): void
    {
        abort_unless(auth()->user()->can('create', User::class), 403);
        $this->importarAberto = true;
        $this->resultadoImportacao = null;
        $this->planilha = null;
        $this->resetValidation();
    }

    public function fecharImportacao(): void
    {
        $this->importarAberto = false;
        $this->planilha = null;
    }

    public function importar(ImportarSociosPlanilha $action): void
    {
        abort_unless(auth()->user()->can('create', User::class), 403);

        $this->validate([
            'planilha' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $caminho = $this->planilha->getRealPath();
        $this->resultadoImportacao = $action->handle(auth()->user(), $caminho);
        $this->planilha = null;

        if ($this->resultadoImportacao['criados'] > 0) {
            session()->flash('status', "{$this->resultadoImportacao['criados']} sócio(s) importado(s) com sucesso.");
        }
    }

    public function render(): View
    {
        $query = User::query()->orderBy('nome');

        if ($this->busca !== '') {
            $query->where(function ($q): void {
                $q->where('nome', 'like', '%'.$this->busca.'%')
                    ->orWhere('matricula', 'like', '%'.$this->busca.'%')
                    ->orWhere('email', 'like', '%'.$this->busca.'%');
            });
        }

        return view('domains.jogadores.lista-socios', [
            'socios' => $query->paginate(12),
            'papeis' => UserRole::cases(),
            'niveis' => NivelJogador::cases(),
            'ehAdmin' => auth()->user()->role === UserRole::Administrador,
        ]);
    }

    private function resetForm(): void
    {
        $this->formAberto = true;
        $this->socioId = null;
        $this->nome = '';
        $this->matricula = '';
        $this->telefone = '';
        $this->email = '';
        $this->role = UserRole::Jogador->value;
        $this->nivel = NivelJogador::Intermediario->value;
        $this->foto = null;
        $this->face = null;
        $this->resetValidation();
    }
}
