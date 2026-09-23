<?php

declare(strict_types=1);

namespace App\Domains\Jogadores\Enums;

enum UserRole: string
{
    case Administrador = 'administrador';
    case Recepcao = 'recepcao';
    case Professor = 'professor';
    case Jogador = 'jogador';

    public function label(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Recepcao => 'Recepção',
            self::Professor => 'Professor',
            self::Jogador => 'Jogador',
        };
    }
}
