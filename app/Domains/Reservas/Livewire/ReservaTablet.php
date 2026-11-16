<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Livewire;

use App\Domains\Configuracoes\ConfiguracaoChave;
use App\Domains\Configuracoes\Services\ConfiguracaoService;
use App\Domains\Filas\Services\FilaEngine;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Enums\UserStatus;
use App\Domains\Jogadores\Models\User;
use App\Domains\Partidas\Enums\Modalidade;
use App\Domains\Presenca\Enums\PresencaMetodo;
use App\Domains\Quadras\Enums\TipoPiso;
use App\Domains\Quadras\Models\Quadra;
use App\Domains\Reservas\Actions\AjustarReservaAoVivo;
use App\Domains\Reservas\Actions\EntrarNoJogo;
use App\Domains\Reservas\Actions\RegistrarPerdaReserva;
use App\Domains\Reservas\Actions\ReservarPelaTablet;
use App\Domains\Reservas\Enums\ReservaOrigem;
use App\Domains\Reservas\Enums\ReservaStatus;
use App\Domains\Reservas\Models\Reserva;
use App\Domains\Reservas\Services\GradeAgenda;
use App\Domains\Reservas\Services\RegistroAtividade;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.kiosk')]
class ReservaTablet extends Component
{
    use WithFileUploads;

    #[Url]
    public string $modo = 'tablet';

    public bool $painelAberto = false;

    public ?int $quadraId = null;

    public string $quadraNome = '';

    public string $modalidade = '';

    public string $hora = '';

    public string $horaFim = '';

    public int $passoJogador = 1;

    public string $codigo = '';

    public string $buscaNome = '';

    /** Como o atleta se identifica no tablet: '' (escolhendo), 'rosto' ou 'codigo'. */
    public string $metodoId = '';

    /** @var array<string, mixed> */
    public array $identificado = [];

    /** Verdadeiro quando o rosto já foi reconhecido pela câmera (não precisa de nova checagem). */
    public bool $viaRosto = false;

    /** @var array<int, array<string, mixed>> */
    public array $jogadores = [];

    public mixed $fotoFacial = null;

    public string $mensagem = '';

    public bool $erro = false;

    public bool $quadraSaibro = false;

    public bool $arrumarQuadra = false;

    public int $duracaoBase = 60;

    /** Reserva existente em que o atleta quer entrar (jogo em andamento). */
    public ?int $entrarReservaId = null;

    /** Timestamp limite para o próximo atleta se cadastrar. */
    public ?int $prazoAte = null;

    /** Verdadeiro quando a secretaria aceitou registrar um sócio bloqueado (usado só no ato de adicionar). */
    public bool $bloqueioLiberado = false;

    public function mount(): void
    {
        if (! in_array($this->modo, ['tablet', 'secretaria'], true)) {
            $this->modo = 'tablet';
        }

        // Só administrador/recepção usam o modo secretaria.
        if ($this->modo === 'secretaria' && ! $this->podeUsarSecretaria()) {
            $this->modo = 'tablet';
        }
    }

    public function usarModo(string $modo): void
    {
        abort_unless($this->podeUsarSecretaria(), 403);

        $this->modo = $modo === 'secretaria' ? 'secretaria' : 'tablet';
        $this->fechar();
    }

    /**
     * Clique em “Reservar” na coluna da quadra.
     * Aplica a regra de ouro: encadeia no fim da última reserva, ou usa agora.
     */
    public function reservarQuadra(int $quadraId, GradeAgenda $grade): void
    {
        $quadra = Quadra::query()->findOrFail($quadraId);
        $tenant = auth()->user()->tenant;

        if ($grade->bloqueadaPelaChuva($tenant, $quadra)) {
            $this->erro = true;
            $this->mensagem = 'Está chovendo: só as quadras cobertas estão liberadas.';

            return;
        }

        $inicio = $grade->proximoInicioDisponivel($quadra, $tenant, $grade->duracaoDaModalidade($tenant, 'simples'));

        if ($inicio === null) {
            $this->erro = true;
            $this->mensagem = empty($tenant->horario_funcionamento)
                ? 'O horário de funcionamento do clube não está cadastrado. Cadastre em Clube.'
                : 'Não há horário disponível nesta quadra agora.';

            return;
        }

        $duracao = $grade->duracaoDaModalidade($tenant, 'simples');

        $this->resetFluxo();
        $this->quadraId = $quadra->id;
        $this->quadraNome = (string) ($quadra->apelido ?: $quadra->nome);
        $this->quadraSaibro = $quadra->tipo_piso === TipoPiso::Saibro;
        $this->duracaoBase = $duracao;
        $this->hora = $inicio->format('H:i');
        $this->horaFim = $inicio->copy()->addMinutes($duracao)->format('H:i');
        $this->painelAberto = true;
    }

    /** Alias usado pelos testes / views antigas. */
    public function abrir(int $quadraId, GradeAgenda $grade): void
    {
        $this->reservarQuadra($quadraId, $grade);
    }

    /**
     * Atleta que chegou depois clica na reserva existente para entrar no jogo.
     */
    public function abrirEntrarNoJogo(int $reservaId): void
    {
        $reserva = Reserva::query()->with(['quadra', 'jogadores', 'user'])->findOrFail($reservaId);

        if ($reserva->status !== ReservaStatus::Confirmada || $reserva->fim->lte(now())) {
            $this->erro = true;
            $this->mensagem = 'Esse jogo já terminou.';

            return;
        }

        if ($reserva->elenco()->count() >= EntrarNoJogo::MAXIMO_JOGADORES) {
            $this->erro = true;
            $this->mensagem = 'Este jogo já está completo (4 jogadores).';

            return;
        }

        $this->resetFluxo();
        $this->entrarReservaId = $reserva->id;
        $this->quadraId = $reserva->quadra_id;
        $this->quadraNome = (string) ($reserva->quadra?->apelido ?: $reserva->quadra?->nome);
        $this->hora = $reserva->inicio->format('H:i');
        $this->horaFim = $reserva->fim->format('H:i');
        $this->painelAberto = true;
    }

    public function escolherModalidade(string $modalidade, GradeAgenda $grade): void
    {
        abort_unless(in_array($modalidade, [Modalidade::Simples->value, Modalidade::Duplas->value], true), 422);

        $this->modalidade = $modalidade;
        $this->passoJogador = 1;
        $this->jogadores = [];
        $this->identificado = [];
        $this->codigo = '';
        $this->viaRosto = false;
        $this->metodoId = '';
        $this->prazoAte = null;
        $this->duracaoBase = $grade->duracaoDaModalidade(auth()->user()->tenant, $modalidade);
        $this->recalcularFim();
    }

    public function definirMetodo(string $metodo): void
    {
        $this->metodoId = in_array($metodo, ['rosto', 'codigo'], true) ? $metodo : '';
        $this->identificado = [];
        $this->viaRosto = false;
        $this->codigo = '';
        $this->resetValidation();
    }

    public function identificarPorCodigo(GradeAgenda $grade): void
    {
        if ($this->prazoVencido()) {
            return;
        }

        $this->validate(['codigo' => ['required', 'string', 'max:20']], [], ['codigo' => 'código']);

        $socio = User::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('matricula', $this->codigo)
            ->where('role', UserRole::Jogador)
            ->first();

        if ($socio === null) {
            $this->addError('codigo', 'Sócio não cadastrado. Peça o cadastro na secretaria.');

            return;
        }

        if ($this->modo === 'tablet' && app(ConfiguracaoService::class)->ativo(ConfiguracaoChave::TABLET_EXIGIR_FACIAL)
            && (! $socio->cadastro_facial_completo || $socio->face_photo_path === null)) {
            $this->addError('codigo', $socio->primeiroNome().' ainda não tem facial cadastrado. Sem isso o tablet não libera a reserva.');

            return;
        }

        $this->aceitarSocio($socio, 'codigo', $grade);
    }

    public function escolherSocio(int $userId, GradeAgenda $grade): void
    {
        abort_unless($this->modo === 'secretaria' && $this->podeUsarSecretaria(), 403);

        $socio = User::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('role', UserRole::Jogador)
            ->findOrFail($userId);

        if ($this->aceitarSocio($socio, 'buscaNome', $grade)) {
            $this->codigo = (string) $socio->matricula;
            $this->buscaNome = '';
        }
    }

    /**
     * O tablet reconheceu o rosto na câmera (1:N): mostra o atleta para ele só dar OK.
     */
    public function identificarPorRosto(int $userId, GradeAgenda $grade): void
    {
        if ($this->prazoVencido()) {
            return;
        }

        $socio = User::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('role', UserRole::Jogador)
            ->whereNotNull('face_photo_path')
            ->find($userId);

        if ($socio === null) {
            $this->addError('foto_facial', 'Não consegui identificar. Tente digitar o código.');

            return;
        }

        if ($this->aceitarSocio($socio, 'foto_facial', $grade)) {
            $this->viaRosto = true;
            $this->codigo = (string) $socio->matricula;
        }
    }

    public function naoSouEu(): void
    {
        $this->identificado = [];
        $this->viaRosto = false;
        $this->codigo = '';
        $this->resetValidation();
    }

    public function confirmarJogador(ConfiguracaoService $config): void
    {
        if ($this->identificado === []) {
            $this->addError('codigo', 'Identifique o jogador primeiro.');

            return;
        }

        if ($this->prazoVencido()) {
            return;
        }

        // Sócio bloqueado: só entra pelo botão "Cadastrar mesmo assim" (decisão da secretaria).
        if (($this->identificado['bloqueio'] ?? null) !== null) {
            $this->addError('codigo', 'Este sócio está bloqueado. Escolha "Cadastrar mesmo assim" ou outra pessoa.');

            return;
        }

        // No tablet com facial obrigatório, a liberação é pelo rosto (reconhecido pela câmera).
        if ($this->modo === 'tablet' && $config->ativo(ConfiguracaoChave::TABLET_EXIGIR_FACIAL) && ! $this->viaRosto) {
            $this->addError('foto_facial', 'Olhe para a câmera até o sistema reconhecer o rosto.');

            return;
        }

        $this->adicionarJogadorIdentificado();
    }

    /**
     * A secretaria viu o aviso de bloqueio (pagamento/pendência) e decidiu registrar mesmo assim.
     */
    public function cadastrarMesmoAssim(): void
    {
        abort_unless($this->ehSecretaria(), 403);

        if ($this->identificado === [] || ($this->identificado['bloqueio'] ?? null) === null) {
            return;
        }

        $this->bloqueioLiberado = true;
        $this->adicionarJogadorIdentificado();
    }

    /**
     * Liberação automática estilo catraca: o browser já reconheceu o rosto ao vivo.
     */
    public function confirmarJogadorPorFacial(ConfiguracaoService $config): void
    {
        if ($this->identificado === []) {
            $this->addError('foto_facial', 'Identifique o jogador primeiro.');

            return;
        }

        abort_unless($this->modo === 'tablet', 403);
        abort_unless($config->ativo(ConfiguracaoChave::TABLET_EXIGIR_FACIAL), 403);

        if ($this->prazoVencido()) {
            return;
        }

        $socio = User::query()->findOrFail($this->identificado['id']);

        if (! $socio->cadastro_facial_completo || $socio->face_photo_path === null) {
            $this->addError('foto_facial', $socio->primeiroNome().' ainda não cadastrou o reconhecimento facial.');

            return;
        }

        if ($mensagem = $this->mensagemSeSocioOcupado($socio)) {
            $this->addError('foto_facial', $mensagem);

            return;
        }

        $this->viaRosto = true;
        $this->adicionarJogadorIdentificado();
    }

    public function salvar(ReservarPelaTablet $action, ConfiguracaoService $config): void
    {
        if ($this->quadraId === null || count($this->jogadores) < $this->totalJogadores) {
            $this->erro = true;
            $this->mensagem = 'Falta escolher a quadra ou os jogadores.';

            return;
        }

        $codigos = array_map(fn (array $jogador): string => (string) $jogador['matricula'], $this->jogadores);
        $presencaFacial = $this->modo === 'tablet' && $config->ativo(ConfiguracaoChave::TABLET_EXIGIR_FACIAL);

        try {
            $reserva = $action->handle(
                Quadra::query()->findOrFail($this->quadraId),
                $codigos[0],
                $codigos[1] ?? null,
                now()->toDateString(),
                null,
                60,
                $this->modalidade !== '' ? $this->modalidade : Modalidade::Simples->value,
                false,
                array_slice($codigos, 2),
                [],
                $this->modo === 'secretaria' ? ReservaOrigem::Recepcao : ReservaOrigem::App,
                $presencaFacial,
                ($this->quadraSaibro && $this->arrumarQuadra) ? 5 : 0,
                $this->ehSecretaria() && collect($this->jogadores)->contains(fn (array $j): bool => ($j['bloqueio'] ?? null) !== null),
            );

            $this->registrarPendencia($this->jogadores);

            $this->resultado('Reserva confirmada na '.$this->quadraNome.': '.$reserva->inicio->format('H:i').' – '.$reserva->fim->format('H:i'));
            $this->resetFluxo();
        } catch (ValidationException $e) {
            $this->resultado((string) collect($e->errors())->flatten()->first(), true);
        }
    }

    /**
     * O outro atleta não se cadastrou a tempo: descarta a reserva em montagem.
     */
    public function expirarPrazo(): void
    {
        if ($this->prazoAte === null || now()->getTimestamp() < $this->prazoAte - 2) {
            return;
        }

        $this->cancelarPorPrazo();
    }

    public function updatedArrumarQuadra(): void
    {
        $this->recalcularFim();
    }

    // ── Ações da secretaria ────────────────────────────────────────────

    public function registrarPerda(int $reservaId, string $motivo, RegistrarPerdaReserva $action): void
    {
        abort_unless($this->podeUsarSecretaria(), 403);

        $reserva = Reserva::query()->findOrFail($reservaId);

        try {
            $action->handle($reserva, $motivo, auth()->user());
            $this->resultado('Perda registrada. A fila desta quadra foi antecipada.');
        } catch (ValidationException $e) {
            $this->resultado((string) collect($e->errors())->flatten()->first(), true);
        }
    }

    public function adicionarMinutos(int $reservaId, int $minutos, AjustarReservaAoVivo $action): void
    {
        abort_unless($this->podeUsarSecretaria(), 403);

        $reserva = Reserva::query()->with('quadra')->findOrFail($reservaId);

        try {
            $action->adicionarMinutos($reserva, $minutos, auth()->user());
            $this->resultado('+'.$minutos.' minutos. Agora termina às '.$reserva->fresh()->fim->format('H:i').'.');
        } catch (ValidationException $e) {
            $this->resultado((string) collect($e->errors())->flatten()->first(), true);
        }
    }

    public function liberarQuadra(int $reservaId, AjustarReservaAoVivo $action): void
    {
        abort_unless($this->podeUsarSecretaria(), 403);

        $reserva = Reserva::query()->with('quadra')->findOrFail($reservaId);

        try {
            $action->liberarQuadra($reserva, auth()->user());
            $this->resultado('Quadra liberada. Quem estava na fila já pode entrar.');
        } catch (ValidationException $e) {
            $this->resultado((string) collect($e->errors())->flatten()->first(), true);
        }
    }

    public function alternarChuva(FilaEngine $engine): void
    {
        abort_unless($this->podeUsarSecretaria(), 403);

        $tenant = auth()->user()->tenant;
        $engine->alternarModoChuva($tenant, ! $tenant->modo_chuva);
        $tenant->refresh();

        $this->resultado($tenant->modo_chuva
            ? 'Modo chuva ligado: só quadras cobertas. Simples 40 min, duplas 60 min.'
            : 'Modo chuva desligado: todas as quadras liberadas.');
    }

    // ── Reconhecimento facial 1:N ──────────────────────────────────────

    /**
     * Lista os atletas com foto facial para o navegador comparar com o rosto da câmera.
     *
     * @return array<int, array<string, mixed>>
     */
    public function carregarCandidatos(): array
    {
        return User::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('role', UserRole::Jogador)
            ->where('bloqueado', false)
            ->whereNotNull('face_photo_path')
            ->orderBy('nome')
            ->limit(600)
            ->get()
            ->map(fn (User $socio): array => [
                'id' => $socio->id,
                'nome' => $socio->nome,
                'face' => $socio->faceUrl,
                'descritor' => is_array($socio->face_descriptor) ? $socio->face_descriptor : null,
            ])
            ->all();
    }

    /**
     * Guarda o vetor facial calculado no navegador (evita recalcular a cada uso).
     *
     * @param  array<int, float|int>  $descritor
     */
    public function salvarDescritor(int $userId, array $descritor): void
    {
        if (count($descritor) !== 128) {
            return;
        }

        $valores = array_map(fn ($valor): float => round((float) $valor, 5), array_values($descritor));

        User::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('role', UserRole::Jogador)
            ->whereKey($userId)
            ->whereNotNull('face_photo_path')
            ->first()
            ?->forceFill(['face_descriptor' => $valores])
            ->saveQuietly();
    }

    // ── Estado da tela ─────────────────────────────────────────────────

    public function fechar(): void
    {
        $this->resetFluxo();
    }

    public function limparResultado(): void
    {
        $this->mensagem = '';
        $this->erro = false;
    }

    #[Computed]
    public function totalJogadores(): int
    {
        return $this->modalidade === Modalidade::Duplas->value ? 4 : 2;
    }

    #[Computed]
    public function podeSecretaria(): bool
    {
        return $this->podeUsarSecretaria();
    }

    public function render(GradeAgenda $grade, ConfiguracaoService $config, RegistroAtividade $atividades): View
    {
        $tenant = auth()->user()->tenant;
        $busca = $this->identificado === [] ? $this->buscarSocios($grade) : [];

        $reservaEntrar = $this->entrarReservaId !== null
            ? Reserva::query()->with(['jogadores', 'user'])->find($this->entrarReservaId)
            : null;

        return view('domains.reservas.reserva-tablet', [
            'agenda' => $grade->montar($tenant),
            'agora' => Carbon::now(),
            'exigirFacial' => $this->modo === 'tablet' && $config->ativo(ConfiguracaoChave::TABLET_EXIGIR_FACIAL),
            'podeSecretaria' => $this->podeSecretaria,
            'totalJogadores' => $this->totalJogadores,
            'busca' => $busca,
            'chuva' => (bool) $tenant->modo_chuva,
            'minutosChuva' => (int) ($config->get(ConfiguracaoChave::CHUVA_DURACAO_MINUTOS)['minutos'] ?? 40),
            'duracoes' => [
                'simples' => $grade->duracaoDaModalidade($tenant, 'simples'),
                'duplas' => $grade->duracaoDaModalidade($tenant, 'duplas'),
            ],
            'prazoRestante' => $this->prazoAte !== null ? max(0, $this->prazoAte - now()->getTimestamp()) : null,
            'atividades' => $this->modo === 'secretaria' ? $atividades->recentes(25) : collect(),
            'jogoDeEntrada' => $reservaEntrar === null ? null : [
                'jogadores' => $grade->cartoes($reservaEntrar->elenco()),
                'vagas' => max(0, EntrarNoJogo::MAXIMO_JOGADORES - $reservaEntrar->elenco()->count()),
            ],
        ]);
    }

    // ── Internos ───────────────────────────────────────────────────────

    /**
     * Busca da secretaria por nome ou código. Código exato aparece primeiro.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buscarSocios(GradeAgenda $grade): array
    {
        $termo = trim($this->buscaNome);

        if ($this->modo !== 'secretaria' || mb_strlen($termo) < 1) {
            return [];
        }

        $exato = User::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('role', UserRole::Jogador)
            ->where('matricula', $termo)
            ->get();

        if (mb_strlen($termo) < 2 && $exato->isEmpty()) {
            return [];
        }

        $parecidos = mb_strlen($termo) < 2 ? collect() : User::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('role', UserRole::Jogador)
            ->where(function ($query) use ($termo): void {
                $query->where('nome', 'like', '%'.$termo.'%')
                    ->orWhere('matricula', 'like', '%'.$termo.'%');
            })
            ->orderBy('nome')
            ->limit(6)
            ->get();

        return $exato->concat($parecidos)
            ->unique('id')
            ->take(6)
            ->map(fn (User $socio): array => $grade->cartao($socio) + [
                'bloqueado' => $socio->bloqueado || $socio->status === UserStatus::Inativo,
                'incompleto' => ! $socio->bloqueado && ! $socio->podeReservarNoTablet(),
            ])
            ->values()
            ->all();
    }

    /** Enter na busca: escolhe o primeiro resultado (útil quando digita o código exato). */
    public function escolherPrimeiroDaBusca(GradeAgenda $grade): void
    {
        abort_unless($this->modo === 'secretaria' && $this->podeUsarSecretaria(), 403);

        $primeiro = $this->buscarSocios($grade)[0] ?? null;

        if ($primeiro === null) {
            $this->addError('buscaNome', 'Ninguém encontrado com esse nome ou código.');

            return;
        }

        $this->escolherSocio((int) $primeiro['id'], $grade);
    }

    /**
     * Valida o atleta para a reserva atual e o apresenta (foto + nome) para confirmação.
     */
    private function aceitarSocio(User $socio, string $campoErro, GradeAgenda $grade): bool
    {
        $secretaria = $this->ehSecretaria();
        $aviso = null;
        $bloqueio = null;

        if ($socio->bloqueado || $socio->status === UserStatus::Inativo) {
            // Bloqueado (pagamento/pendência) ou inativo: só a secretaria decide se registra mesmo assim.
            if (! $secretaria) {
                $this->addError($campoErro, 'Seu cadastro está bloqueado. Procure a secretaria.');

                return false;
            }

            $bloqueio = $socio->bloqueado
                ? $socio->primeiroNome().' está BLOQUEADO (pagamento ou pendência com o clube).'
                : $socio->primeiroNome().' está com o cadastro INATIVO.';
        } elseif (! $socio->podeReservarNoTablet()) {
            // Cadastro incompleto: a secretaria registra na quadra e arruma o cadastro depois.
            if (! $secretaria) {
                $this->addError($campoErro, 'Cadastro incompleto. Procure a secretaria para registrar você.');

                return false;
            }

            $aviso = 'Cadastro incompleto (ainda não confirmou o cadastro/facial). Pode registrar na quadra; arrume depois em Sócios.';
        }

        if (collect($this->jogadores)->contains(fn (array $item): bool => $item['id'] === $socio->id)
            || $this->jaEstaNoJogoDeEntrada($socio)) {
            $this->addError($campoErro, $socio->primeiroNome().' já está nesta reserva.');

            return false;
        }

        if ($mensagem = $this->mensagemSeSocioOcupado($socio)) {
            $this->addError($campoErro, $mensagem);

            return false;
        }

        // Sócio pendente com facial já feito: libera para jogar.
        if ($aviso === null && $bloqueio === null && $socio->status === UserStatus::Pendente) {
            $socio->forceFill([
                'status' => UserStatus::Ativo,
                'email_verified_at' => $socio->email_verified_at ?? now(),
                'confirmacao_token' => null,
            ])->save();
        }

        $this->identificado = $grade->cartao($socio) + ['aviso' => $aviso, 'bloqueio' => $bloqueio];
        $this->resetValidation();

        return true;
    }

    private function jaEstaNoJogoDeEntrada(User $socio): bool
    {
        if ($this->entrarReservaId === null) {
            return false;
        }

        $reserva = Reserva::query()->with(['jogadores', 'user'])->find($this->entrarReservaId);

        return $reserva !== null
            && $reserva->elenco()->contains(fn (User $jogador): bool => $jogador->id === $socio->id);
    }

    private function adicionarJogadorIdentificado(): void
    {
        $presenca = $this->viaRosto ? PresencaMetodo::Facial : PresencaMetodo::Tablet;

        if ($this->entrarReservaId !== null) {
            $this->confirmarEntradaNoJogo($presenca);

            return;
        }

        $this->jogadores[] = $this->identificado;
        $this->identificado = [];
        $this->bloqueioLiberado = false;
        $this->codigo = '';
        $this->buscaNome = '';
        $this->fotoFacial = null;
        $this->viaRosto = false;
        $this->metodoId = '';
        $this->resetValidation();

        if (count($this->jogadores) < $this->totalJogadores) {
            $this->passoJogador++;
            $this->prazoAte = $this->modo === 'tablet' ? now()->getTimestamp() + $this->prazoSegundos() : null;
        } else {
            $this->prazoAte = null;
        }
    }

    private function confirmarEntradaNoJogo(PresencaMetodo $presenca): void
    {
        $reserva = Reserva::query()->with(['quadra', 'jogadores', 'user'])->find($this->entrarReservaId);
        $atleta = User::query()->find($this->identificado['id'] ?? 0);

        if ($reserva === null || $atleta === null) {
            $this->resultado('Esse jogo não está mais na agenda.', true);
            $this->resetFluxo();

            return;
        }

        try {
            $comPendencia = ($this->identificado['bloqueio'] ?? null) !== null || ($this->identificado['aviso'] ?? null) !== null;

            app(EntrarNoJogo::class)->handle($reserva, $atleta, $presenca, $this->bloqueioLiberado && $this->ehSecretaria());

            if ($comPendencia) {
                $this->registrarPendencia([$this->identificado]);
            }

            $this->resultado($atleta->primeiroNome().' entrou no jogo da '.$this->quadraNome.'. O horário continua o mesmo.');
        } catch (ValidationException $e) {
            $this->resultado((string) collect($e->errors())->flatten()->first(), true);
        }

        $this->bloqueioLiberado = false;
        $this->resetFluxo();
    }

    /**
     * Deixa na linha do tempo que a secretaria registrou alguém com cadastro incompleto ou bloqueado.
     *
     * @param  array<int, array<string, mixed>>  $jogadores
     */
    private function registrarPendencia(array $jogadores): void
    {
        foreach ($jogadores as $jogador) {
            $motivo = $jogador['bloqueio'] ?? $jogador['aviso'] ?? null;

            if ($motivo === null) {
                continue;
            }

            app(RegistroAtividade::class)->registrar(
                'reserva_com_pendencia',
                'Registrado pela secretaria · '.$this->quadraNome,
                $jogador['nome'].': '.$motivo,
                $this->quadraId,
                (int) $jogador['id'],
                auth()->user()->tenant_id,
            );
        }
    }

    private function prazoSegundos(): int
    {
        $segundos = (int) (app(ConfiguracaoService::class)->get(ConfiguracaoChave::TABLET_PRAZO_CADASTRO_SEGUNDOS)['segundos'] ?? 60);

        return max(15, min(600, $segundos));
    }

    /** Se o tempo entre atletas estourou, desfaz o cadastro e avisa. */
    private function prazoVencido(): bool
    {
        if ($this->prazoAte === null || now()->getTimestamp() <= $this->prazoAte + 3) {
            return false;
        }

        $this->cancelarPorPrazo();

        return true;
    }

    private function cancelarPorPrazo(): void
    {
        $nomes = collect($this->jogadores)->pluck('primeiro_nome')->join(' e ');
        $quadra = $this->quadraNome;

        app(RegistroAtividade::class)->registrar(
            'cadastro_expirado',
            'Cadastro não concluído · '.$quadra,
            ($nomes !== '' ? $nomes.' cadastrou(aram), mas ' : '').'o outro atleta não chegou a tempo. Reserva removida.',
            $this->quadraId,
            tenantId: auth()->user()->tenant_id,
        );

        $this->resetFluxo();
        $this->resultado('Tempo esgotado: o outro atleta não se cadastrou. A reserva foi removida e a quadra está livre.', true);
    }

    private function resultado(string $texto, bool $erro = false): void
    {
        $this->mensagem = $texto;
        $this->erro = $erro;
    }

    private function mensagemSeSocioOcupado(User $socio): ?string
    {
        $emOutraQuadra = Reserva::ocupacaoEmOutraQuadra($socio, $this->quadraId);

        if ($emOutraQuadra !== null) {
            return $emOutraQuadra->mensagemConflitoSocio($socio);
        }

        if ($this->hora === '' || $this->horaFim === '') {
            return null;
        }

        $inicio = Carbon::parse(now()->toDateString().' '.$this->hora);
        $fim = Carbon::parse(now()->toDateString().' '.$this->horaFim);

        if ($fim->lte($inicio)) {
            $fim->addDay();
        }

        $ocupado = Reserva::conflitoDoSocio($socio, $inicio, $fim, $this->entrarReservaId);

        return $ocupado?->mensagemConflitoSocio($socio);
    }

    private function recalcularFim(): void
    {
        if ($this->hora === '') {
            return;
        }

        $extra = ($this->quadraSaibro && $this->arrumarQuadra) ? 5 : 0;
        $inicio = Carbon::parse(now()->toDateString().' '.$this->hora);
        $this->horaFim = $inicio->copy()->addMinutes($this->duracaoBase + $extra)->format('H:i');
    }

    private function ehSecretaria(): bool
    {
        return $this->modo === 'secretaria' && $this->podeUsarSecretaria();
    }

    private function podeUsarSecretaria(): bool
    {
        return in_array(auth()->user()?->role, [
            UserRole::Administrador,
            UserRole::Recepcao,
        ], true);
    }

    private function resetFluxo(): void
    {
        $this->painelAberto = false;
        $this->quadraId = null;
        $this->quadraNome = '';
        $this->modalidade = '';
        $this->hora = '';
        $this->horaFim = '';
        $this->passoJogador = 1;
        $this->codigo = '';
        $this->buscaNome = '';
        $this->metodoId = '';
        $this->identificado = [];
        $this->viaRosto = false;
        $this->jogadores = [];
        $this->fotoFacial = null;
        $this->quadraSaibro = false;
        $this->arrumarQuadra = false;
        $this->duracaoBase = 60;
        $this->entrarReservaId = null;
        $this->prazoAte = null;
        $this->bloqueioLiberado = false;
        unset($this->totalJogadores, $this->podeSecretaria);
        $this->resetValidation();
    }
}
