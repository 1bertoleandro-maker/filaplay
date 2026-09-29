<?php

declare(strict_types=1);

namespace App\Domains\Quadras\Http;

use App\Domains\Filas\Services\FilaEngine;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Reservas\Services\GradeAgenda;
use Illuminate\Contracts\View\View;

final class PainelTvController
{
    public function __invoke(FilaEngine $engine, GradeAgenda $grade): View
    {
        abort_unless(in_array(auth()->user()?->role, [
            UserRole::Administrador,
            UserRole::Recepcao,
            UserRole::Professor,
        ], true), 403);

        $tenant = auth()->user()->tenant;
        $engine->processarNoShows($tenant);

        return view('domains.quadras.painel-tv', [
            'agenda' => $grade->montar($tenant),
            'agora' => now(),
            'clube' => $tenant,
        ]);
    }
}
