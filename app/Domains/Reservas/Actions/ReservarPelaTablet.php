<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Actions;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Clube\Services\HorarioDoClube;
use App\Domains\Configuracoes\ConfiguracaoChave;
use App\Domains\Configuracoes\Services\ConfiguracaoService;
use App\Domains\Jogadores\Models\User;
use App\Domains\Partidas\Enums\Modalidade;
use App\Domains\Presenca\Contracts\FaceRecognitionService;
use App\Domains\Presenca\Enums\PresencaMetodo;
use App\Domains\Presenca\Models\Presenca;
use App\Domains\Quadras\Models\Bloqueio;
use App\Domains\Quadras\Models\Quadra;
use App\Domains\Reservas\Enums\ReservaOrigem;
use App\Domains\Reservas\Enums\ReservaStatus;
use App\Domains\Reservas\Models\Reserva;
use App\Domains\Reservas\Services\GradeAgenda;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reserva presencial na grade do tablet (sócio) ou da secretaria (admin).
 * Horário contínuo: arredonda a chegada em 5 min e encadeia no fim da reserva anterior.
 */
final class ReservarPelaTablet
{
    public function __construct(
        private readonly HorarioDoClube $horario,
        private readonly ConfiguracaoService $config,
        private readonly FaceRecognitionService $faces,
        private readonly GradeAgenda $grade,
    ) {}

    /**
     * @param  array<int, string>  $codigos
     * @param  array<string, UploadedFile>  $fotos
     */
    public function handle(
        Quadra $quadra,
        string $matriculaPrincipal,
        ?string $matriculaParceiro,
        string $data,
        ?string $hora = null,
        int $duracaoMinutos = 60,
        string $modalidade = 'simples',
        bool $validarFacial = false,
        array $codigosExtras = [],
        array $fotos = [],
        ReservaOrigem $origem = ReservaOrigem::App,
        bool $presencaFacialJaValidada = false,
    ): Reserva {
        $tenant = $quadra->tenant;
        $codigos = array_values(array_filter([
            $matriculaPrincipal,
            $matriculaParceiro,
            ...$codigosExtras,
        ], fn (?string $codigo): bool => filled($codigo)));

        $esperados = $modalidade === Modalidade::Duplas->value ? 4 : 2;

        if (count($codigos) !== $esperados) {
            throw ValidationException::withMessages([
                'matricula' => $esperados === 4
                    ? 'Duplas precisa de 4 jogadores.'
                    : 'Simples precisa de 2 jogadores.',
            ]);
        }

        if (count($codigos) !== count(array_unique($codigos))) {
            throw ValidationException::withMessages([
                'matricula' => 'Cada jogador só pode entrar uma vez nesta reserva.',
            ]);
        }

        $jogadores = [];

        foreach ($codigos as $indice => $codigo) {
            $jogador = $this->buscarSocio($tenant->id, $codigo, $indice === 0 ? 'matricula' : 'matricula_parceiro');

            if ($validarFacial) {
                $this->reconhecer($jogador, $fotos[$codigo] ?? $fotos[(string) $jogador->id] ?? null);
            }

            $jogadores[] = $jogador;
        }

        $chaveDuracao = $modalidade === Modalidade::Duplas->value
            ? ConfiguracaoChave::MODALIDADE_DUPLAS_MINUTOS
            : ConfiguracaoChave::MODALIDADE_SIMPLES_MINUTOS;

        $duracao = (int) ($this->config->get($chaveDuracao)['minutos'] ?? $duracaoMinutos);

        $inicio = $this->resolverInicio($quadra, $tenant, $data, $hora, $duracao);
        $fim = $inicio->copy()->addMinutes($duracao);

        if ($fim->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages(['hora' => 'Esse horário já passou.']);
        }

        if (! $this->horario->cabe($tenant, $inicio, $fim)) {
            throw ValidationException::withMessages(['hora' => 'Fora do horário de funcionamento do clube.']);
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
            throw ValidationException::withMessages(['hora' => 'Este horário já está ocupado.']);
        }

        foreach ($jogadores as $jogador) {
            $ocupado = Reserva::conflitoDoSocio($jogador, $inicio, $fim);

            if ($ocupado !== null) {
                throw ValidationException::withMessages([
                    'matricula' => $ocupado->mensagemConflitoSocio($jogador),
                ]);
            }
        }

        $usouFacial = $validarFacial || $presencaFacialJaValidada;
        $nomes = collect($jogadores)->map(fn (User $jogador): string => $jogador->nome)->implode(', ');
        $metodo = $usouFacial ? PresencaMetodo::Facial : PresencaMetodo::Tablet;
        $observacao = ($usouFacial ? 'Validado por reconhecimento facial. ' : '').'Reservado por '.$nomes;

        return DB::transaction(function () use ($quadra, $tenant, $jogadores, $inicio, $fim, $observacao, $modalidade, $origem, $metodo): Reserva {
            $principal = $jogadores[0];

            $reserva = Reserva::query()->create([
                'tenant_id' => $tenant->id,
                'quadra_id' => $quadra->id,
                'user_id' => $principal->id,
                'inicio' => $inicio,
                'fim' => $fim,
                'origem' => $origem,
                'status' => ReservaStatus::Confirmada,
                'modalidade' => $modalidade,
                'observacoes' => $observacao,
            ]);

            foreach ($jogadores as $ordem => $pessoa) {
                $reserva->jogadores()->attach($pessoa->id, [
                    'tenant_id' => $tenant->id,
                    'ordem' => $ordem + 1,
                ]);

                Presenca::query()->create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $pessoa->id,
                    'metodo' => $metodo,
                    'validado_em' => now(),
                ]);
            }

            return $reserva->load('jogadores');
        });
    }

    private function resolverInicio(
        Quadra $quadra,
        Tenant $tenant,
        string $data,
        ?string $hora,
        int $duracao,
    ): Carbon {
        $proximoLivre = $this->grade->proximoInicioDisponivel($quadra, $tenant, $duracao);

        if ($proximoLivre === null) {
            throw ValidationException::withMessages([
                'hora' => 'Não há horário disponível nesta quadra hoje.',
            ]);
        }

        if ($hora === null || trim($hora) === '') {
            return $proximoLivre;
        }

        $pedido = Carbon::parse($data.' '.$hora);

        if ($pedido->lte($proximoLivre) || abs($pedido->diffInMinutes($proximoLivre)) <= 1) {
            return $proximoLivre;
        }

        return $this->grade->arredondarParaCinco($pedido);
    }

    private function buscarSocio(int $tenantId, string $matricula, string $campo): User
    {
        $socio = User::query()
            ->where('tenant_id', $tenantId)
            ->where('matricula', $matricula)
            ->first();

        if ($socio === null) {
            throw ValidationException::withMessages([$campo => 'Código não encontrado.']);
        }

        if ($socio->bloqueado) {
            throw ValidationException::withMessages([$campo => 'Este sócio está bloqueado.']);
        }

        return $socio;
    }

    private function reconhecer(User $socio, mixed $fotoFacial): void
    {
        if (! $fotoFacial instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'foto_facial' => "Tire a foto do rosto de {$socio->primeiroNome()} para validar.",
            ]);
        }

        if (! $socio->cadastro_facial_completo || $socio->face_photo_path === null) {
            throw ValidationException::withMessages([
                'foto_facial' => "{$socio->primeiroNome()} ainda não tem cadastro facial. Peça o link por e-mail ou WhatsApp.",
            ]);
        }

        $referencia = $this->faces->enroll($socio->face_photo_path);
        $caminho = $fotoFacial->getRealPath();

        if ($caminho === false || ! $this->faces->compare($referencia, $caminho)) {
            throw ValidationException::withMessages([
                'foto_facial' => "Não reconhecemos o rosto de {$socio->primeiroNome()}. Tente de novo.",
            ]);
        }
    }
}
