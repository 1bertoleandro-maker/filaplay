<?php

declare(strict_types=1);

namespace App\Domains\Jogadores\Enums;

enum UserStatus: string
{
    case Ativo = 'ativo';
    case Inativo = 'inativo';
    case Pendente = 'pendente';

    public function label(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::Inativo => 'Inativo',
            self::Pendente => 'Pendente',
        };
    }
}
