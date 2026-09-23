<?php

declare(strict_types=1);

namespace App\Domains\Comunicacao\Services;

use App\Domains\Comunicacao\Contracts\ClubNotifier;
use App\Domains\Comunicacao\Enums\ComunicacaoCanal;
use App\Domains\Comunicacao\Enums\ComunicacaoStatus;
use App\Domains\Comunicacao\Models\Comunicacao;

final class LogClubNotifier implements ClubNotifier
{
    public function send(
        int $tenantId,
        ComunicacaoCanal $canal,
        string $destino,
        string $corpo,
        ?int $userId = null,
        ?string $assunto = null,
    ): Comunicacao {
        return Comunicacao::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'canal' => $canal,
            'destino' => $destino,
            'assunto' => $assunto,
            'corpo' => $corpo,
            'status' => ComunicacaoStatus::Registrado,
        ]);
    }
}
