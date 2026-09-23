<?php

declare(strict_types=1);

namespace App\Domains\Quadras\Http;

use App\Domains\Filas\Enums\FilaStatus;
use App\Domains\Filas\Models\Fila;
use App\Domains\Filas\Services\FilaEngine;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Partidas\Enums\PartidaStatus;
use App\Domains\Partidas\Models\Partida;
use App\Domains\Quadras\Enums\QuadraStatus;
use App\Domains\Quadras\Models\Bloqueio;
use App\Domains\Quadras\Models\Quadra;
use App\Domains\Reservas\Enums\ReservaStatus;
use App\Domains\Reservas\Models\Reserva;
use Illuminate\Contracts\View\View;

final class PainelTvController
{
    public function __invoke(FilaEngine $engine): View
    {
        abort_unless(in_array(auth()->user()?->role, [
            UserRole::Administrador,
            UserRole::Recepcao,
            UserRole::Professor,
        ], true), 403);

        $tenant = auth()->user()->tenant;
        $engine->processarNoShows($tenant);

        $agora = now();
        $quadras = Quadra::query()->orderBy('ordem_exibicao')->get();

        $reservasDoDia = Reserva::query()
            ->with('user')
            ->where('status', ReservaStatus::Confirmada)
            ->whereDate('inicio', $agora->toDateString())
            ->orderBy('inicio')
            ->get()
            ->groupBy('quadra_id');

        $partidasAtivas = Partida::query()
            ->with('jogadores')
            ->where('status', PartidaStatus::EmAndamento)
            ->get()
            ->keyBy('quadra_id');

        $filasPorQuadra = Fila::query()
            ->with('user')
            ->whereIn('status', [FilaStatus::Aguardando, FilaStatus::Chamado])
            ->orderByDesc('prioridade')
            ->orderBy('posicao')
            ->get()
            ->groupBy('quadra_id');

        $bloqueiosAtivos = Bloqueio::query()
            ->where('inicio', '<=', $agora)
            ->where('fim', '>', $agora)
            ->get()
            ->keyBy('quadra_id');

        $cards = $quadras->map(function (Quadra $quadra) use ($agora, $reservasDoDia, $partidasAtivas, $filasPorQuadra, $bloqueiosAtivos): array {
            $agenda = $reservasDoDia->get($quadra->id, collect());

            $reservaAtual = $agenda->first(fn (Reserva $reserva): bool => $reserva->inicio->lessThanOrEqualTo($agora)
                && $reserva->fim->greaterThan($agora));

            $partida = $partidasAtivas->get($quadra->id);
            $bloqueio = $bloqueiosAtivos->get($quadra->id);

            $fila = $filasPorQuadra->get($quadra->id, collect());

            $fimPartida = $partida
                ? $partida->inicio_real->copy()->addMinutes($partida->duracao_minutos + $partida->tempo_extra)
                : null;

            return [
                'quadra' => $quadra,
                'bloqueio' => $bloqueio,
                'partida' => $partida,
                'fim_partida' => $fimPartida,
                'reserva_atual' => $reservaAtual,
                'aguardando' => $fila->where('status', FilaStatus::Aguardando)->take(4),
                'chamados' => $fila->where('status', FilaStatus::Chamado),
                'agenda' => $agenda,
                'ocupada' => $partida !== null || $reservaAtual !== null || $quadra->status === QuadraStatus::Ocupada,
            ];
        });

        return view('domains.quadras.painel-tv', [
            'cards' => $cards,
            'agora' => $agora,
            'clube' => $tenant,
        ]);
    }
}
