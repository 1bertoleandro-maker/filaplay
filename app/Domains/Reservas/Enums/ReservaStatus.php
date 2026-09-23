<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Enums;

enum ReservaStatus: string
{
    case Confirmada = 'confirmada';
    case Cancelada = 'cancelada';
    case Concluida = 'concluida';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Confirmada => 'Confirmada',
            self::Cancelada => 'Cancelada',
            self::Concluida => 'Concluída',
            self::NoShow => 'No-show',
        };
    }
}
