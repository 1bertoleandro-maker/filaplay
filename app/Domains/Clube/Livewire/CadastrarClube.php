<?php

declare(strict_types=1);

namespace App\Domains\Clube\Livewire;

use App\Domains\Clube\Actions\CadastrarNovoClube;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class CadastrarClube extends Component
{
    public string $nome_clube = '';

    public string $nome_responsavel = '';

    public string $email = '';

    public string $telefone = '';

    public string $senha = '';

    public string $senha_confirmation = '';

    public ?string $google_id = null;

    public bool $enviado = false;

    public function mount(): void
    {
        $this->nome_responsavel = (string) session('google_nome', '');
        $this->email = (string) session('google_email', '');
        $this->google_id = session('google_id');
    }

    public function salvar(CadastrarNovoClube $action): void
    {
        if ($this->google_id === null) {
            $this->validate([
                'senha' => ['required', 'string', 'min:8', 'same:senha_confirmation'],
            ]);
        }

        $action->handle([
            'nome_clube' => $this->nome_clube,
            'nome_responsavel' => $this->nome_responsavel,
            'email' => $this->email,
            'telefone' => $this->telefone !== '' ? $this->telefone : null,
            'senha' => $this->senha,
        ], $this->google_id);

        session()->forget(['google_nome', 'google_email', 'google_id']);
        $this->enviado = true;
    }

    public function render(): View
    {
        return view('domains.clube.cadastrar-clube');
    }
}
