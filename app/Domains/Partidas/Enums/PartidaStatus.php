<?php

declare(strict_types=1);

namespace App\Domains\Partidas\Enums;

enum PartidaStatus: string
{
    case Agendada = 'agendada';
    case EmAndamento = 'em_andamento';
    case Encerrada = 'encerrada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Agendada => 'Agendada',
            self::EmAndamento => 'Em andamento',
            self::Encerrada => 'Encerrada',
            self::Cancelada => 'Cancelada',
        };
    }
}
