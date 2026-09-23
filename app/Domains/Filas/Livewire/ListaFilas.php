<?php

declare(strict_types=1);

namespace App\Domains\Filas\Livewire;

use App\Domains\Filas\Enums\FilaStatus;
use App\Domains\Filas\Models\Fila;
use App\Domains\Filas\Services\FilaEngine;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Models\User;
use App\Domains\Partidas\Enums\Modalidade;
use App\Domains\Partidas\Enums\PartidaStatus;
use App\Domains\Partidas\Models\Partida;
use App\Domains\Quadras\Models\Quadra;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ListaFilas extends Component
{
    public ?int $quadra_id = null;

    public ?int $user_id = null;

    public string $modalidade = 'duplas';

    public function mount(FilaEngine $engine): void
    {
        abort_unless(in_array(auth()->user()?->role, [
            UserRole::Administrador,
            UserRole::Recepcao,
            UserRole::Professor,
        ], true), 403);

        $engine->processarNoShows(auth()->user()->tenant);
    }

    public function entrar(FilaEngine $engine): void
    {
        $quadra = Quadra::query()->findOrFail((int) $this->quadra_id);
        $socio = User::query()->findOrFail((int) $this->user_id);

        $engine->entrar($quadra, $socio, Modalidade::from($this->modalidade));

        $this->user_id = null;
        session()->flash('status', 'Jogador adicionado à fila.');
    }

    public function sair(FilaEngine $engine, int $filaId): void
    {
        $fila = Fila::query()->findOrFail($filaId);
        $engine->sair($fila);
        session()->flash('status', 'Jogador removido da fila.');
    }

    public function chamarProximos(FilaEngine $engine, int $quadraId): void
    {
        $quadra = Quadra::query()->findOrFail($quadraId);
        $engine->chamarProximos($quadra);
    }

    public function confirmarManual(FilaEngine $engine, int $filaId): void
    {
        $fila = Fila::query()->findOrFail($filaId);
        $engine->confirmarChamada($fila->user);
        session()->flash('status', 'Presença confirmada pela secretaria.');
    }

    public function tempoExtra(FilaEngine $engine, int $partidaId): void
    {
        $partida = Partida::query()->findOrFail($partidaId);
        $engine->adicionarTempoExtra($partida);
        session()->flash('status', 'Tempo extra adicionado.');
    }

    public function encerrarPartida(FilaEngine $engine, int $partidaId): void
    {
        $partida = Partida::query()->findOrFail($partidaId);
        $engine->encerrarPartida($partida);
        session()->flash('status', 'Quadra liberada.');
    }

    public function render(): View
    {
        $quadras = Quadra::query()->orderBy('ordem_exibicao')->get();

        $filas = Fila::query()
            ->with('user')
            ->whereIn('status', [FilaStatus::Aguardando, FilaStatus::Chamado, FilaStatus::Jogando])
            ->orderByDesc('prioridade')
            ->orderBy('posicao')
            ->get()
            ->groupBy('quadra_id');

        $partidasAtivas = Partida::query()
            ->with('jogadores')
            ->where('status', PartidaStatus::EmAndamento)
            ->get()
            ->keyBy('quadra_id');

        $quadrasComFila = $quadras->map(fn (Quadra $quadra): array => [
            'quadra' => $quadra,
            'aguardando' => $filas->get($quadra->id, collect())->where('status', FilaStatus::Aguardando),
            'chamados' => $filas->get($quadra->id, collect())->where('status', FilaStatus::Chamado),
            'partida' => $partidasAtivas->get($quadra->id),
        ]);

        return view('domains.filas.lista-filas', [
            'quadrasComFila' => $quadrasComFila,
            'quadras' => $quadras,
            'socios' => User::query()->where('bloqueado', false)->orderBy('nome')->get(),
            'modalidades' => Modalidade::cases(),
        ]);
    }
}
