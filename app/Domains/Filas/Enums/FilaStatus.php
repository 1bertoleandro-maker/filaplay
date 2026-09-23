<?php

declare(strict_types=1);

namespace App\Domains\Filas\Enums;

enum FilaStatus: string
{
    case Aguardando = 'aguardando';
    case Chamado = 'chamado';
    case Jogando = 'jogando';
    case Removido = 'removido';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Aguardando => 'Aguardando',
            self::Chamado => 'Chamado',
            self::Jogando => 'Jogando',
            self::Removido => 'Removido',
            self::NoShow => 'No-show',
        };
    }
}
