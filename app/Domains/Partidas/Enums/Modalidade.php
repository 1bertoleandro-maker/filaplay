<?php

declare(strict_types=1);

namespace App\Domains\Partidas\Enums;

enum Modalidade: string
{
    case Simples = 'simples';
    case Duplas = 'duplas';
    case Octeto = 'octeto';
    case Ranking = 'ranking';

    public function label(): string
    {
        return match ($this) {
            self::Simples => 'Simples',
            self::Duplas => 'Duplas',
            self::Octeto => 'Octeto',
            self::Ranking => 'Ranking',
        };
    }

    public function jogadores(): ?int
    {
        return match ($this) {
            self::Simples => 2,
            self::Duplas => 4,
            self::Octeto => 8,
            self::Ranking => null,
        };
    }
}
