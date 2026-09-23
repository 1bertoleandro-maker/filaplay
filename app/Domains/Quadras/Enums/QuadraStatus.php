<?php

declare(strict_types=1);

namespace App\Domains\Quadras\Enums;

enum QuadraStatus: string
{
    case Disponivel = 'disponivel';
    case Ocupada = 'ocupada';
    case Manutencao = 'manutencao';
    case Bloqueada = 'bloqueada';

    public function label(): string
    {
        return match ($this) {
            self::Disponivel => 'Disponível',
            self::Ocupada => 'Ocupada',
            self::Manutencao => 'Manutenção',
            self::Bloqueada => 'Bloqueada',
        };
    }
}
