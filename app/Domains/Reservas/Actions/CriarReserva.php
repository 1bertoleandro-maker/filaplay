<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Actions;

use App\Domains\Clube\Services\HorarioDoClube;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Models\User;
use App\Domains\Presenca\Contracts\FaceRecognitionService;
use App\Domains\Presenca\Enums\PresencaMetodo;
use App\Domains\Presenca\Models\Presenca;
use App\Domains\Quadras\Models\Bloqueio;
use App\Domains\Quadras\Models\Quadra;
use App\Domains\Reservas\Enums\ReservaOrigem;
use App\Domains\Reservas\Enums\ReservaStatus;
use App\Domains\Reservas\Models\Reserva;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CriarReserva
{
    public function __construct(
        private readonly HorarioDoClube $horario,
        private readonly FaceRecognitionService $faces,
    ) {}

    public function handle(
        User $actor,
        int $quadraId,
        int $socioId,
        string $data,
        string $hora,
        int $duracaoMinutos,
        string $validacao,
        ?UploadedFile $fotoFacial = null,
    ): Reserva {
        if (! in_array($actor->role, [UserRole::Administrador, UserRole::Recepcao], true)) {
            abort(403);
        }

        $inicio = Carbon::parse($data.' '.$hora);
        $fim = $inicio->copy()->addMinutes($duracaoMinutos);
        $tenant = $actor->tenant;

        if ($fim->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages([
                'hora' => 'Esse horário já passou.',
            ]);
        }

        if (! $this->horario->cabe($tenant, $inicio, $fim)) {
            throw ValidationException::withMessages([
                'hora' => 'Fora do horário de funcionamento do clube.',
            ]);
        }

        $quadra = Quadra::query()->find($quadraId);
        $socio = User::query()->find($socioId);

        if ($quadra === null || $socio === null) {
            throw ValidationException::withMessages([
                'quadra_id' => 'Quadra ou sócio inválido.',
            ]);
        }

        if ($socio->bloqueado) {
            throw ValidationException::withMessages([
                'user_id' => 'Este sócio está bloqueado.',
            ]);
        }

        $conflito = Reserva::query()
            ->where('quadra_id', $quadra->id)
            ->where('status', ReservaStatus::Confirmada)
            ->where('inicio', '<', $fim)
            ->where('fim', '>', $inicio)
            ->exists();

        $bloqueio = Bloqueio::query()
            ->where('quadra_id', $quadra->id)
            ->where('inicio', '<', $fim)
            ->where('fim', '>', $inicio)
            ->exists();

        if ($conflito || $bloqueio) {
            throw ValidationException::withMessages([
                'hora' => 'Este horário já está ocupado.',
            ]);
        }

        $metodo = $validacao === 'facial' ? PresencaMetodo::Facial : PresencaMetodo::Tablet;
        $observacao = 'Validado pela secretaria.';

        if ($metodo === PresencaMetodo::Facial) {
            $this->reconhecer($socio, $fotoFacial);
            $observacao = 'Validado por reconhecimento facial.';
        }

        return DB::transaction(function () use ($actor, $quadra, $socio, $inicio, $fim, $metodo, $observacao): Reserva {
            $reserva = Reserva::query()->create([
                'tenant_id' => $actor->tenant_id,
                'quadra_id' => $quadra->id,
                'user_id' => $socio->id,
                'inicio' => $inicio,
                'fim' => $fim,
                'origem' => ReservaOrigem::Recepcao,
                'status' => ReservaStatus::Confirmada,
                'observacoes' => $observacao,
            ]);

            Presenca::query()->create([
                'tenant_id' => $actor->tenant_id,
                'user_id' => $socio->id,
                'metodo' => $metodo,
                'validado_em' => now(),
            ]);

            return $reserva;
        });
    }

    private function reconhecer(User $socio, ?UploadedFile $fotoFacial): void
    {
        if ($fotoFacial === null) {
            throw ValidationException::withMessages([
                'foto_facial' => 'Envie a foto do rosto para validar.',
            ]);
        }

        if (! $socio->cadastro_facial_completo || $socio->face_photo_path === null) {
            throw ValidationException::withMessages([
                'foto_facial' => 'Este sócio ainda não tem cadastro facial.',
            ]);
        }

        $referencia = $this->faces->enroll($socio->face_photo_path);
        $caminho = $fotoFacial->getRealPath();

        if ($caminho === false || ! $this->faces->compare($referencia, $caminho)) {
            throw ValidationException::withMessages([
                'foto_facial' => 'Rosto não reconhecido. A secretaria pode confirmar manualmente.',
            ]);
        }
    }
}
