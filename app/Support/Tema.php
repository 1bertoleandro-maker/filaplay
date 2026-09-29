<?php

declare(strict_types=1);

namespace App\Support;

use App\Domains\Configuracoes\ConfiguracaoChave;
use App\Domains\Configuracoes\Services\ConfiguracaoService;

final class Tema
{
    public static function claro(): bool
    {
        if (auth()->user() === null) {
            return false;
        }

        $valor = app(ConfiguracaoService::class)->get(ConfiguracaoChave::APARENCIA_TEMA);

        return ($valor['tema'] ?? 'escuro') === 'claro';
    }

    public static function classeHtml(): string
    {
        return self::claro() ? 'tema-claro' : 'dark';
    }
}
