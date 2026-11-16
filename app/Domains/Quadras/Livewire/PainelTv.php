<?php

declare(strict_types=1);

namespace App\Domains\Quadras\Livewire;

use App\Domains\Filas\Services\FilaEngine;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Reservas\Models\Atividade;
use App\Domains\Reservas\Models\Reserva;
use App\Domains\Reservas\Services\GradeAgenda;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * TV do clube. Atualiza sozinha (sem recarregar a página) para o som e os
 * alertas em tela cheia não serem interrompidos.
 */
#[Layout('layouts.kiosk')]
class PainelTv extends Component
{
    public function mount(): void
    {
        abort_unless(in_array(auth()->user()?->role, [
            UserRole::Administrador,
            UserRole::Recepcao,
            UserRole::Professor,
        ], true), 403);
    }

    public function render(FilaEngine $engine, GradeAgenda $grade): View
    {
        $tenant = auth()->user()->tenant;
        $engine->processarNoShows($tenant);
        $agenda = $grade->montar($tenant);

        $chamadas = collect($agenda['colunas'])
            ->map(function (array $coluna): ?array {
                $proxima = $coluna['proxima'] ?? null;

                if (! $proxima instanceof Reserva) {
                    return null;
                }

                $modalidade = (string) $proxima->modalidade;

                if (! in_array($modalidade, ['simples', 'duplas'], true)) {
                    return null;
                }

                $segundos = $proxima->inicio->getTimestamp() - now()->getTimestamp();

                // Avisa quando faltam 2 minutos; o aviso fica na tela por cerca de 1 minuto.
                if ($segundos < 0 || $segundos > 120) {
                    return null;
                }

                $quadra = $coluna['quadra'];

                return [
                    'id' => $proxima->id,
                    'quadra' => $quadra->apelido ?: $quadra->nome,
                    'inicio' => $proxima->inicio->format('H:i'),
                    'fim' => $proxima->fim->format('H:i'),
                    'modalidade' => $modalidade === 'duplas' ? 'Duplas' : 'Simples',
                    'nomes' => collect($coluna['jogadores_proxima'])->pluck('primeiro_nome')->join(' · '),
                ];
            })
            ->filter()
            ->values();

        $novas = Atividade::query()
            ->where('tipo', 'reserva_criada')
            ->where('created_at', '>=', now()->subMinutes(10))
            ->with('quadra')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn (Atividade $atividade): array => [
                'id' => $atividade->id,
                'quadra' => (string) ($atividade->quadra?->apelido ?: $atividade->quadra?->nome ?: 'Quadra'),
                'detalhe' => (string) $atividade->detalhe,
            ])
            ->values();

        return view('domains.quadras.painel-tv', [
            'agenda' => $agenda,
            'agora' => now(),
            'clube' => $tenant,
            'chamadas' => $chamadas,
            'novas' => $novas,
            'ultimoId' => (int) (Atividade::query()->max('id') ?? 0),
        ]);
    }
}
