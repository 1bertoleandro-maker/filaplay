<?php

declare(strict_types=1);

namespace App\Domains\Clube\Enums;

enum TenantStatus: string
{
    case Pendente = 'pendente';
    case Ativo = 'ativo';
    case Bloqueado = 'bloqueado';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Aguardando aprovação',
            self::Ativo => 'Ativo',
            self::Bloqueado => 'Bloqueado',
        };
    }
}
