<?php

declare(strict_types=1);

namespace App\Domains\Comunicacao\Enums;

enum ComunicacaoCanal: string
{
    case Whatsapp = 'whatsapp';
    case Email = 'email';
}
