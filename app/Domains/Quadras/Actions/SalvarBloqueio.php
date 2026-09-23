<?php

declare(strict_types=1);

namespace App\Domains\Quadras\Actions;

use App\Domains\Quadras\Enums\BloqueioTipo;
use App\Domains\Quadras\Models\Bloqueio;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

final class SalvarBloqueio
{
    /**
     * @return array<int, Bloqueio>
     */
    public function handle(
        int $tenantId,
        int $quadraId,
        BloqueioTipo $tipo,
        string $data,
        string $horaInicio,
        string $horaFim,
        string $motivo,
        bool $recorrente = false,
        int $semanas = 1,
    ): array {
        $inicio = Carbon::parse($data.' '.$horaInicio);
        $fim = Carbon::parse($data.' '.$horaFim);

        if ($fim->lessThanOrEqualTo($inicio)) {
            throw ValidationException::withMessages(['fim' => 'O fim deve ser depois do início.']);
        }

        $criados = [];
        $repeticoes = $recorrente ? max(1, min(52, $semanas)) : 1;

        for ($i = 0; $i < $repeticoes; $i++) {
            $criados[] = Bloqueio::query()->create([
                'tenant_id' => $tenantId,
                'quadra_id' => $quadraId,
                'tipo' => $tipo,
                'inicio' => $inicio->copy()->addWeeks($i),
                'fim' => $fim->copy()->addWeeks($i),
                'motivo' => $motivo,
                'recorrente' => $recorrente,
            ]);
        }

        return $criados;
    }
}
