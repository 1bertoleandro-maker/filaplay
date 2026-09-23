<?php

declare(strict_types=1);

namespace App\Domains\Comunicacao\Enums;

enum ComunicacaoStatus: string
{
    case Registrado = 'registrado';
    case Enviado = 'enviado';
    case Falhou = 'falhou';
}
