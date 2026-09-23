<?php

declare(strict_types=1);

namespace App\Domains\Comunicacao\Contracts;

use App\Domains\Comunicacao\Enums\ComunicacaoCanal;
use App\Domains\Comunicacao\Models\Comunicacao;

interface ClubNotifier
{
    public function send(
        int $tenantId,
        ComunicacaoCanal $canal,
        string $destino,
        string $corpo,
        ?int $userId = null,
        ?string $assunto = null,
    ): Comunicacao;
}
