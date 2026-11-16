<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Actions;

use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Models\User;
use App\Domains\Reservas\Enums\ReservaStatus;
use App\Domains\Reservas\Models\Reserva;
use App\Domains\Reservas\Services\RegistroAtividade;
use App\Domains\Reservas\Services\ReorganizaFilaDaQuadra;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ações da secretaria sobre uma reserva em andamento:
 * - somar minutos (quem vem depois é empurrado);
 * - avisar que os atletas já saíram (quadra livre e fila antecipada).
 */
final class AjustarReservaAoVivo
{
    public function __construct(
        private readonly ReorganizaFilaDaQuadra $fila,
        private readonly RegistroAtividade $atividades,
    ) {}

    public function adicionarMinutos(Reserva $reserva, int $minutos, User $actor): void
    {
        $this->autorizar($actor);
        $this->garantirAtiva($reserva);

        DB::transaction(fn () => $this->fila->estender($reserva, $minutos));

        $reserva->refresh()->loadMissing('quadra');
        $this->atividades->registrar(
            'tempo_extra',
            '+'.$minutos.' min · '.$this->nomeQuadra($reserva),
            'Agora termina às '.$reserva->fim->format('H:i').'.',
            $reserva->quadra_id,
            tenantId: $reserva->tenant_id,
        );
    }

    public function liberarQuadra(Reserva $reserva, User $actor): void
    {
        $this->autorizar($actor);
        $this->garantirAtiva($reserva);

        DB::transaction(function () use ($reserva): void {
            $reserva->update([
                'status' => ReservaStatus::Concluida,
                'fim' => $reserva->inicio->gt(now()) ? $reserva->inicio : now()->seconds(0),
                'observacoes' => trim((string) $reserva->observacoes.' Atletas saíram antes do fim.'),
            ]);

            $this->fila->antecipar($reserva);
        });

        $reserva->loadMissing('quadra');
        $this->atividades->registrar(
            'quadra_liberada',
            'Quadra livre · '.$this->nomeQuadra($reserva),
            'Os atletas já saíram. A fila andou.',
            $reserva->quadra_id,
            tenantId: $reserva->tenant_id,
        );
    }

    private function autorizar(User $actor): void
    {
        abort_unless(in_array($actor->role, [UserRole::Administrador, UserRole::Recepcao], true), 403);
    }

    private function garantirAtiva(Reserva $reserva): void
    {
        if ($reserva->status !== ReservaStatus::Confirmada) {
            throw ValidationException::withMessages(['reserva' => 'Essa reserva já não está ativa.']);
        }
    }

    private function nomeQuadra(Reserva $reserva): string
    {
        return (string) ($reserva->quadra?->apelido ?: $reserva->quadra?->nome);
    }
}
