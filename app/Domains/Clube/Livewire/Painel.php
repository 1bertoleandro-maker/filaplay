<?php

declare(strict_types=1);

namespace App\Domains\Clube\Livewire;

use App\Domains\Filas\Models\Fila;
use App\Domains\Filas\Models\NoShow;
use App\Domains\Jogadores\Models\User;
use App\Domains\Partidas\Enums\PartidaStatus;
use App\Domains\Partidas\Models\Partida;
use App\Domains\Quadras\Enums\QuadraStatus;
use App\Domains\Quadras\Models\Quadra;
use App\Domains\Reservas\Enums\ReservaStatus;
use App\Domains\Reservas\Models\Reserva;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Painel extends Component
{
    public function render(): View
    {
        $totalQuadras = Quadra::query()->count();
        $ocupadas = Quadra::query()->where('status', QuadraStatus::Ocupada)->count();

        $noShowsHoje = NoShow::query()->whereDate('registrado_em', now()->toDateString())->count();
        $reservasHoje = Reserva::query()
            ->where('status', ReservaStatus::Confirmada)
            ->whereDate('inicio', now()->toDateString())
            ->count();

        $temposDeEspera = Fila::query()
            ->whereNotNull('partida_id')
            ->whereDate('horario_entrada', now()->toDateString())
            ->with('partida')
            ->get()
            ->map(function (Fila $fila): ?float {
                if ($fila->partida?->inicio_real === null) {
                    return null;
                }

                return (float) $fila->horario_entrada->diffInMinutes($fila->partida->inicio_real);
            })
            ->filter(fn (?float $minutos): bool => $minutos !== null);

        $tempoMedioEspera = $temposDeEspera->isEmpty() ? 0 : round($temposDeEspera->avg());

        return view('domains.clube.painel', [
            'quadras' => $totalQuadras,
            'cobertas' => Quadra::query()->where('coberta', true)->count(),
            'socios' => User::query()->count(),
            'ocupacaoPercentual' => $totalQuadras > 0 ? (int) round($ocupadas / $totalQuadras * 100) : 0,
            'partidasHoje' => Partida::query()
                ->where('status', PartidaStatus::EmAndamento)
                ->orWhere(function ($query): void {
                    $query->where('status', PartidaStatus::Encerrada)
                        ->whereDate('inicio_real', now()->toDateString());
                })
                ->count(),
            'tempoMedioEspera' => $tempoMedioEspera,
            'noShowsHoje' => $noShowsHoje,
            'reservasHoje' => $reservasHoje,
        ]);
    }
}
