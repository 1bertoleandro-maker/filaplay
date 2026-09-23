<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Enums;

enum ReservaOrigem: string
{
    case App = 'app';
    case Recepcao = 'recepcao';
    case Professor = 'professor';

    public function label(): string
    {
        return match ($this) {
            self::App => 'Aplicativo',
            self::Recepcao => 'Recepção',
            self::Professor => 'Professor',
        };
    }
}
