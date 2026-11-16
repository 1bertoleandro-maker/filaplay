<?php

declare(strict_types=1);

namespace App\Support;

/** Canal de contato da FilaPlay para contratar ou ampliar o plano. */
final class Contato
{
    public static function url(): string
    {
        return (string) config('filaplay.contato_url');
    }

    public static function rotulo(): string
    {
        return (string) config('filaplay.contato_rotulo');
    }
}
