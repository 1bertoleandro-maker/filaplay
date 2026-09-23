<?php

declare(strict_types=1);

namespace App\Domains\Filas\Livewire;

use App\Domains\Filas\Enums\FilaStatus;
use App\Domains\Filas\Models\Fila;
use App\Domains\Filas\Services\FilaEngine;
use App\Domains\Jogadores\Models\User;
use App\Domains\Partidas\Enums\Modalidade;
use App\Domains\Presenca\Actions\RegistrarPresenca;
use App\Domains\Quadras\Models\Quadra;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.kiosk')]
class Kiosk extends Component
{
    public Quadra $quadra;

    public string $mensagem = '';

    public bool $erro = false;

    public function mount(Quadra $quadra, FilaEngine $engine): void
    {
        $this->quadra = $quadra;
        $engine->processarNoShows($quadra->tenant);
    }

    public function confirmarPresenca(RegistrarPresenca $action, string $matricula): void
    {
        try {
            $jogador = $action->porMatricula($this->quadra->tenant, $matricula);
            $this->mensagem = 'Presença confirmada, '.$jogador->nome.'!';
            $this->erro = false;
        } catch (ValidationException $e) {
            $this->mensagem = $this->primeiraMensagem($e);
            $this->erro = true;
        }
    }

    public function entrarNaFila(FilaEngine $engine, string $matricula, string $modalidade): void
    {
        try {
            $jogador = User::query()
                ->where('tenant_id', $this->quadra->tenant_id)
                ->where('matricula', $matricula)
                ->first();

            if ($jogador === null) {
                throw ValidationException::withMessages(['matricula' => 'Matrícula não encontrada.']);
            }

            $engine->entrar($this->quadra, $jogador, Modalidade::from($modalidade));
            $this->mensagem = $jogador->nome.' entrou na fila!';
            $this->erro = false;
        } catch (ValidationException $e) {
            $this->mensagem = $this->primeiraMensagem($e);
            $this->erro = true;
        }
    }

    public function sairDaFila(FilaEngine $engine, string $matricula): void
    {
        try {
            $jogador = User::query()
                ->where('tenant_id', $this->quadra->tenant_id)
                ->where('matricula', $matricula)
                ->first();

            $fila = $jogador ? Fila::query()
                ->where('user_id', $jogador->id)
                ->where('quadra_id', $this->quadra->id)
                ->whereIn('status', [FilaStatus::Aguardando, FilaStatus::Chamado])
                ->first() : null;

            if ($fila === null) {
                throw ValidationException::withMessages(['matricula' => 'Você não está na fila desta quadra.']);
            }

            $engine->sair($fila);
            $this->mensagem = 'Você saiu da fila.';
            $this->erro = false;
        } catch (ValidationException $e) {
            $this->mensagem = $this->primeiraMensagem($e);
            $this->erro = true;
        }
    }

    public function limpar(): void
    {
        $this->mensagem = '';
        $this->erro = false;
    }

    private function primeiraMensagem(ValidationException $e): string
    {
        return (string) collect($e->errors())->flatten()->first();
    }

    public function render(): View
    {
        return view('domains.filas.kiosk', [
            'modalidades' => Modalidade::cases(),
        ]);
    }
}
