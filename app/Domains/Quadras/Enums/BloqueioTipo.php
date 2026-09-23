<?php

declare(strict_types=1);

namespace App\Domains\Quadras\Enums;

enum BloqueioTipo: string
{
    case Torneio = 'torneio';
    case Aula = 'aula';
    case Manutencao = 'manutencao';
    case Evento = 'evento';

    public function label(): string
    {
        return match ($this) {
            self::Torneio => 'Torneio',
            self::Aula => 'Aula',
            self::Manutencao => 'Manutenção',
            self::Evento => 'Evento',
        };
    }
}
