<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Actions;

use App\Domains\Jogadores\Models\User;
use App\Domains\Partidas\Enums\Modalidade;
use App\Domains\Presenca\Enums\PresencaMetodo;
use App\Domains\Presenca\Models\Presenca;
use App\Domains\Reservas\Enums\ReservaStatus;
use App\Domains\Reservas\Models\Reserva;
use App\Domains\Reservas\Services\RegistroAtividade;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Um atleta que chegou depois entra numa reserva que já existe.
 * O horário não muda; ele só não pode estar jogando em outra quadra.
 */
final class EntrarNoJogo
{
    public const MAXIMO_JOGADORES = 4;

    public function __construct(private readonly RegistroAtividade $atividades) {}

    public function handle(
        Reserva $reserva,
        User $atleta,
        PresencaMetodo $metodo = PresencaMetodo::Tablet,
        bool $liberarBloqueado = false,
    ): Reserva {
        $reserva->loadMissing(['quadra', 'jogadores', 'user']);

        if ($reserva->status !== ReservaStatus::Confirmada || $reserva->fim->lte(now())) {
            throw ValidationException::withMessages(['reserva' => 'Esse jogo já terminou.']);
        }

        if ($atleta->bloqueado && ! $liberarBloqueado) {
            throw ValidationException::withMessages(['atleta' => $atleta->primeiroNome().' está bloqueado.']);
        }

        $elenco = $reserva->elenco();

        if ($elenco->contains(fn (User $jogador): bool => $jogador->id === $atleta->id)) {
            throw ValidationException::withMessages(['atleta' => $atleta->primeiroNome().' já está neste jogo.']);
        }

        if ($elenco->count() >= self::MAXIMO_JOGADORES) {
            throw ValidationException::withMessages(['atleta' => 'Este jogo já está completo (4 jogadores).']);
        }

        $emOutra = Reserva::ocupacaoEmOutraQuadra($atleta, $reserva->quadra_id);

        if ($emOutra !== null) {
            throw ValidationException::withMessages(['atleta' => $emOutra->mensagemConflitoSocio($atleta)]);
        }

        $conflito = Reserva::conflitoDoSocio($atleta, $reserva->inicio, $reserva->fim, $reserva->id);

        if ($conflito !== null) {
            throw ValidationException::withMessages(['atleta' => $conflito->mensagemConflitoSocio($atleta)]);
        }

        DB::transaction(function () use ($reserva, $atleta, $metodo, $elenco): void {
            // Reservas antigas (sem tabela de jogadores) guardam só o titular.
            if ($reserva->jogadores->isEmpty() && $reserva->user !== null) {
                $reserva->jogadores()->attach($reserva->user->id, [
                    'tenant_id' => $reserva->tenant_id,
                    'ordem' => 1,
                ]);
            }

            $reserva->jogadores()->attach($atleta->id, [
                'tenant_id' => $reserva->tenant_id,
                'ordem' => $elenco->count() + 1,
            ]);

            if ($elenco->count() + 1 > 2) {
                $reserva->update(['modalidade' => Modalidade::Duplas->value]);
            }

            Presenca::query()->create([
                'tenant_id' => $reserva->tenant_id,
                'user_id' => $atleta->id,
                'metodo' => $metodo,
                'validado_em' => now(),
            ]);
        });

        $reserva->unsetRelation('jogadores');
        $reserva->load('jogadores');

        $this->atividades->registrar(
            'jogador_entrou',
            $atleta->primeiroNome().' entrou no jogo · '.($reserva->quadra?->apelido ?: $reserva->quadra?->nome),
            'Reserva das '.$reserva->inicio->format('H:i').'. O horário não mudou.',
            $reserva->quadra_id,
            $atleta->id,
            $reserva->tenant_id,
        );

        return $reserva;
    }
}
