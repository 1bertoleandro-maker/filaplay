<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Livewire;

use App\Domains\Configuracoes\ConfiguracaoChave;
use App\Domains\Configuracoes\Services\ConfiguracaoService;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Enums\UserStatus;
use App\Domains\Jogadores\Models\User;
use App\Domains\Partidas\Enums\Modalidade;
use App\Domains\Quadras\Models\Quadra;
use App\Domains\Reservas\Actions\ReservarPelaTablet;
use App\Domains\Reservas\Enums\ReservaOrigem;
use App\Domains\Reservas\Models\Reserva;
use App\Domains\Reservas\Services\GradeAgenda;
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

    /** @var array<string, mixed> */
    public array $identificado = [];

    /** @var array<int, array<string, mixed>> */
    public array $jogadores = [];

    public mixed $fotoFacial = null;

    public string $mensagem = '';

    public bool $erro = false;

    public function mount(): void
    {
        if (! in_array($this->modo, ['tablet', 'secretaria'], true)) {
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

        $inicio = $grade->proximoInicioDisponivel($quadra, $tenant);

        if ($inicio === null) {
            $this->erro = true;
            $this->mensagem = 'Não há horário disponível nesta quadra agora.';

            return;
        }

        $duracao = $grade->duracaoPadraoMinutos();

        $this->resetFluxo();
        $this->quadraId = $quadra->id;
        $this->quadraNome = (string) ($quadra->apelido ?: $quadra->nome);
        $this->hora = $inicio->format('H:i');
        $this->horaFim = $inicio->copy()->addMinutes($duracao)->format('H:i');
        $this->painelAberto = true;
    }

    /** Alias usado pelos testes / views antigas. */
    public function abrir(int $quadraId, GradeAgenda $grade): void
    {
        $this->reservarQuadra($quadraId, $grade);
    }

    public function escolherModalidade(string $modalidade): void
    {
        abort_unless(in_array($modalidade, [Modalidade::Simples->value, Modalidade::Duplas->value], true), 422);

        $this->modalidade = $modalidade;
        $this->passoJogador = 1;
        $this->jogadores = [];
        $this->identificado = [];
        $this->codigo = '';
    }

    public function identificarPorCodigo(GradeAgenda $grade): void
    {
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

        if ($socio->bloqueado || $socio->status === UserStatus::Inativo) {
            $this->addError('codigo', 'Este sócio está bloqueado ou inativo.');

            return;
        }

        if (! $socio->podeReservarNoTablet()) {
            $this->addError('codigo', 'Cadastro incompleto. A secretaria precisa ativar este sócio.');

            return;
        }

        if ($this->modo === 'tablet' && app(ConfiguracaoService::class)->ativo(ConfiguracaoChave::TABLET_EXIGIR_FACIAL)
            && (! $socio->cadastro_facial_completo || $socio->face_photo_path === null)) {
            $this->addError('codigo', $socio->primeiroNome().' ainda não tem facial cadastrado. Sem isso o tablet não libera a reserva.');

            return;
        }

        // Sócio pendente com facial já feito: libera para jogar.
        if ($socio->status === UserStatus::Pendente) {
            $socio->forceFill([
                'status' => UserStatus::Ativo,
                'email_verified_at' => $socio->email_verified_at ?? now(),
                'confirmacao_token' => null,
            ])->save();
        }

        if (collect($this->jogadores)->contains(fn (array $item): bool => $item['id'] === $socio->id)) {
            $this->addError('codigo', 'Essa pessoa já está nesta reserva.');

            return;
        }

        if ($mensagem = $this->mensagemSeSocioOcupado($socio)) {
            $this->addError('codigo', $mensagem);

            return;
        }

        $this->identificado = $grade->cartao($socio);
        $this->resetValidation();
    }

    public function escolherSocio(int $userId, GradeAgenda $grade): void
    {
        abort_unless($this->modo === 'secretaria', 403);

        $socio = User::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('role', UserRole::Jogador)
            ->findOrFail($userId);

        if ($socio->bloqueado || $socio->status === UserStatus::Inativo) {
            $this->addError('buscaNome', 'Este sócio está bloqueado ou inativo.');

            return;
        }

        if (! $socio->podeReservarNoTablet()) {
            $this->addError('buscaNome', 'Cadastro incompleto. Ative o sócio antes de reservar.');

            return;
        }

        if ($socio->status === UserStatus::Pendente) {
            $socio->forceFill([
                'status' => UserStatus::Ativo,
                'email_verified_at' => $socio->email_verified_at ?? now(),
                'confirmacao_token' => null,
            ])->save();
        }

        if (collect($this->jogadores)->contains(fn (array $item): bool => $item['id'] === $socio->id)) {
            $this->addError('buscaNome', 'Essa pessoa já está nesta reserva.');

            return;
        }

        if ($mensagem = $this->mensagemSeSocioOcupado($socio)) {
            $this->addError('buscaNome', $mensagem);

            return;
        }

        $this->identificado = $grade->cartao($socio);
        $this->codigo = (string) $socio->matricula;
        $this->buscaNome = '';
    }

    public function confirmarJogador(ConfiguracaoService $config): void
    {
        if ($this->identificado === []) {
            $this->addError('codigo', 'Identifique o jogador primeiro.');

            return;
        }

        // No tablet com facial obrigatório, a liberação é pela catraca (câmera contínua).
        if ($this->modo === 'tablet' && $config->ativo(ConfiguracaoChave::TABLET_EXIGIR_FACIAL)) {
            $this->addError('foto_facial', 'Olhe para a câmera até o sistema reconhecer o rosto.');

            return;
        }

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

        $socio = User::query()->findOrFail($this->identificado['id']);

        if (! $socio->cadastro_facial_completo || $socio->face_photo_path === null) {
            $this->addError('foto_facial', $socio->primeiroNome().' ainda não cadastrou o reconhecimento facial.');

            return;
        }

        if ($mensagem = $this->mensagemSeSocioOcupado($socio)) {
            $this->addError('foto_facial', $mensagem);

            return;
        }

        $this->adicionarJogadorIdentificado();
    }

    private function adicionarJogadorIdentificado(): void
    {
        $this->jogadores[] = $this->identificado;
        $this->identificado = [];
        $this->codigo = '';
        $this->buscaNome = '';
        $this->fotoFacial = null;
        $this->resetValidation();

        if (count($this->jogadores) < $this->totalJogadores) {
            $this->passoJogador++;
        }
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
            );

            $this->mensagem = 'Reserva confirmada na '.$this->quadraNome.': '.$reserva->inicio->format('H:i').' – '.$reserva->fim->format('H:i');
            $this->erro = false;
            $this->painelAberto = false;
        } catch (ValidationException $e) {
            $this->erro = true;
            $this->mensagem = (string) collect($e->errors())->flatten()->first();
        }
    }

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

    public function render(GradeAgenda $grade, ConfiguracaoService $config): View
    {
        $tenant = auth()->user()->tenant;
        $busca = [];

        if ($this->modo === 'secretaria' && mb_strlen($this->buscaNome) >= 2) {
            $busca = User::query()
                ->where('tenant_id', $tenant->id)
                ->where('role', UserRole::Jogador)
                ->where('bloqueado', false)
                ->where(function ($query): void {
                    $query->where('nome', 'like', '%'.$this->buscaNome.'%')
                        ->orWhere('matricula', 'like', '%'.$this->buscaNome.'%');
                })
                ->orderBy('nome')
                ->limit(6)
                ->get()
                ->map(fn (User $socio): array => $grade->cartao($socio))
                ->all();
        }

        return view('domains.reservas.reserva-tablet', [
            'agenda' => $grade->montar($tenant),
            'agora' => Carbon::now(),
            'exigirFacial' => $this->modo === 'tablet' && $config->ativo(ConfiguracaoChave::TABLET_EXIGIR_FACIAL),
            'podeSecretaria' => $this->podeSecretaria,
            'totalJogadores' => $this->totalJogadores,
            'busca' => $busca,
        ]);
    }

    private function mensagemSeSocioOcupado(User $socio): ?string
    {
        if ($this->hora === '' || $this->horaFim === '') {
            return null;
        }

        $inicio = Carbon::parse(now()->toDateString().' '.$this->hora);
        $fim = Carbon::parse(now()->toDateString().' '.$this->horaFim);

        if ($fim->lte($inicio)) {
            $fim->addDay();
        }

        $ocupado = Reserva::conflitoDoSocio($socio, $inicio, $fim);

        return $ocupado?->mensagemConflitoSocio($socio);
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
        $this->identificado = [];
        $this->jogadores = [];
        $this->fotoFacial = null;
        unset($this->totalJogadores, $this->podeSecretaria);
        $this->resetValidation();
    }
}
