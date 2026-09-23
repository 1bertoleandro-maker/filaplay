<?php

declare(strict_types=1);

namespace App\Domains\Quadras\Enums;

enum TipoPiso: string
{
    case Saibro = 'saibro';
    case Duro = 'duro';
    case Grama = 'grama';
    case Areia = 'areia';

    public function label(): string
    {
        return match ($this) {
            self::Saibro => 'Saibro',
            self::Duro => 'Piso duro',
            self::Grama => 'Grama',
            self::Areia => 'Areia',
        };
    }
}
