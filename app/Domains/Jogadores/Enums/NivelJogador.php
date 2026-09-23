<?php

declare(strict_types=1);

namespace App\Domains\Jogadores\Enums;

enum NivelJogador: string
{
    case Iniciante = 'iniciante';
    case Intermediario = 'intermediario';
    case Avancado = 'avancado';
    case Profissional = 'profissional';

    public function label(): string
    {
        return match ($this) {
            self::Iniciante => 'Iniciante',
            self::Intermediario => 'Intermediário',
            self::Avancado => 'Avançado',
            self::Profissional => 'Profissional',
        };
    }
}
