<?php

declare(strict_types=1);

namespace App\Domains\Filas\Services;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Configuracoes\ConfiguracaoChave;
use App\Domains\Configuracoes\Services\ConfiguracaoService;
use App\Domains\Filas\Enums\FilaStatus;
use App\Domains\Filas\Models\Fila;
use App\Domains\Filas\Models\NoShow;
use App\Domains\Jogadores\Models\User;
use App\Domains\Partidas\Enums\Modalidade;
use App\Domains\Partidas\Enums\PartidaStatus;
use App\Domains\Partidas\Models\Partida;
use App\Domains\Presenca\Models\Presenca;
use App\Domains\Quadras\Enums\QuadraStatus;
use App\Domains\Quadras\Models\Bloqueio;
use App\Domains\Quadras\Models\Quadra;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Motor da fila: entrada/saída, chamada dos próximos, confirmação,
 * no-show automático, tempo extra e modo chuva.
 */
final class FilaEngine
{
    public function __construct(private readonly ConfiguracaoService $config) {}

    /**
     * Jogador entra na fila de uma quadra para uma modalidade.
     */
    public function entrar(Quadra $quadra, User $jogador, Modalidade $modalidade): Fila
    {
        $tenant = $quadra->tenant;

        $this->garantirQuadraDisponivelParaFila($tenant, $quadra);
        $this->garantirSemDuplicidade($jogador);
        $this->garantirPresencaValidada($jogador);

        $posicao = (int) Fila::query()
            ->where('quadra_id', $quadra->id)
            ->where('status', FilaStatus::Aguardando)
            ->max('posicao') + 1;

        $fila = Fila::query()->create([
            'tenant_id' => $tenant->id,
            'quadra_id' => $quadra->id,
            'user_id' => $jogador->id,
            'modalidade' => $modalidade,
            'prioridade' => 0,
            'posicao' => $posicao,
            'horario_entrada' => now(),
            'status' => FilaStatus::Aguardando,
        ]);

        $this->recalcularEstimativas($quadra);
        $this->chamarProximos($quadra->fresh());

        return $fila->fresh();
    }

    public function sair(Fila $fila): void
    {
        if (! in_array($fila->status, [FilaStatus::Aguardando, FilaStatus::Chamado], true)) {
            throw ValidationException::withMessages(['fila' => 'Este jogador não está mais aguardando.']);
        }

        $quadra = $fila->quadra;
        $fila->update(['status' => FilaStatus::Removido]);

        $this->recalcularEstimativas($quadra);
        $this->chamarProximos($quadra->fresh());
    }

    /**
     * Chama o próximo grupo de jogadores para a quadra livre, se houver
     * jogadores suficientes aguardando para a modalidade da frente da fila.
     */
    public function chamarProximos(Quadra $quadra): void
    {
        $jaChamado = Fila::query()
            ->where('quadra_id', $quadra->id)
            ->whereIn('status', [FilaStatus::Chamado, FilaStatus::Jogando])
            ->exists();

        if ($jaChamado) {
            return;
        }

        if ($this->quadraBloqueadaAgora($quadra) || $quadra->status === QuadraStatus::Manutencao) {
            return;
        }

        $tenant = $quadra->tenant;

        if ($tenant->modo_chuva && ! $quadra->coberta) {
            return;
        }

        $proximo = Fila::query()
            ->where('quadra_id', $quadra->id)
            ->where('status', FilaStatus::Aguardando)
            ->orderByDesc('prioridade')
            ->orderBy('posicao')
            ->first();

        if ($proximo === null) {
            return;
        }

        $necessarios = $this->quantidadeDeJogadores($proximo->modalidade);

        $grupo = Fila::query()
            ->where('quadra_id', $quadra->id)
            ->where('status', FilaStatus::Aguardando)
            ->orderByDesc('prioridade')
            ->orderBy('posicao')
            ->limit($necessarios)
            ->get();

        if ($grupo->count() < $necessarios) {
            return;
        }

        $segundos = (int) ($this->config->get(ConfiguracaoChave::NO_SHOW_SEGUNDOS)['segundos'] ?? 300);

        DB::transaction(function () use ($grupo, $segundos): void {
            foreach ($grupo as $entrada) {
                $entrada->update([
                    'status' => FilaStatus::Chamado,
                    'chamado_em' => now(),
                    'confirmar_at' => now()->addSeconds($segundos),
                ]);
            }
        });
    }

    /**
     * Confirma a entrada de um jogador chamado. Quando todo o grupo
     * confirmar, a partida é iniciada automaticamente.
     */
    public function confirmarChamada(User $jogador): ?Fila
    {
        $fila = Fila::query()
            ->where('user_id', $jogador->id)
            ->where('status', FilaStatus::Chamado)
            ->first();

        if ($fila === null) {
            return null;
        }

        $fila->update(['status' => FilaStatus::Jogando]);

        $quadra = $fila->quadra;
        $grupo = Fila::query()
            ->where('quadra_id', $quadra->id)
            ->where('chamado_em', $fila->chamado_em)
            ->whereIn('status', [FilaStatus::Chamado, FilaStatus::Jogando])
            ->get();

        $todosConfirmaram = $grupo->every(fn (Fila $item): bool => $item->status === FilaStatus::Jogando);

        if ($todosConfirmaram) {
            $this->iniciarPartida($quadra, $grupo);
        }

        return $fila->fresh();
    }

    private function iniciarPartida(Quadra $quadra, Collection $grupo): void
    {
        $tenant = $quadra->tenant;
        $modalidade = $grupo->first()->modalidade;
        $duracao = $this->duracaoModalidade($tenant, $modalidade, $quadra);

        DB::transaction(function () use ($quadra, $grupo, $modalidade, $duracao, $tenant): void {
            $partida = Partida::query()->create([
                'tenant_id' => $tenant->id,
                'quadra_id' => $quadra->id,
                'modalidade' => $modalidade,
                'duracao_minutos' => $duracao,
                'inicio_previsto' => now(),
                'inicio_real' => now(),
                'tempo_extra' => 0,
                'status' => PartidaStatus::EmAndamento,
            ]);

            $partida->jogadores()->attach($grupo->pluck('user_id')->all(), ['tenant_id' => $tenant->id]);

            Fila::query()
                ->whereIn('id', $grupo->pluck('id'))
                ->update(['partida_id' => $partida->id]);

            $quadra->update(['status' => QuadraStatus::Ocupada]);
        });
    }

    /**
     * Encerra a partida em andamento de uma quadra e chama os próximos.
     */
    public function encerrarPartida(Partida $partida): void
    {
        $quadra = $partida->quadra;

        DB::transaction(function () use ($partida, $quadra): void {
            $partida->update(['fim_real' => now(), 'status' => PartidaStatus::Encerrada]);

            Fila::query()
                ->where('partida_id', $partida->id)
                ->update(['status' => FilaStatus::Removido]);

            if (! $this->quadraBloqueadaAgora($quadra)) {
                $quadra->update(['status' => QuadraStatus::Disponivel]);
            }
        });

        $this->recalcularEstimativas($quadra->fresh());
        $this->chamarProximos($quadra->fresh());
    }

    public function adicionarTempoExtra(Partida $partida, ?int $minutos = null): void
    {
        $minutos ??= (int) ($this->config->get(ConfiguracaoChave::TEMPO_EXTRA_MINUTOS)['minutos'] ?? 5);

        $partida->increment('tempo_extra', $minutos);

        $this->recalcularEstimativas($partida->quadra);
    }

    /**
     * Processa chamadas expiradas (no-show) em todas as quadras do tenant
     * do jogador informado — chamado a cada carregamento das telas de
     * fila/kiosk/TV para simular verificação contínua sem cron.
     */
    public function processarNoShows(Tenant $tenant): void
    {
        $expirados = Fila::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', FilaStatus::Chamado)
            ->where('confirmar_at', '<', now())
            ->get()
            ->groupBy('quadra_id');

        foreach ($expirados as $quadraId => $entradas) {
            DB::transaction(function () use ($entradas): void {
                foreach ($entradas as $entrada) {
                    NoShow::query()->create([
                        'tenant_id' => $entrada->tenant_id,
                        'user_id' => $entrada->user_id,
                        'quadra_id' => $entrada->quadra_id,
                        'fila_id' => $entrada->id,
                        'registrado_em' => now(),
                    ]);

                    $entrada->update(['status' => FilaStatus::NoShow]);
                }
            });

            $quadra = Quadra::query()->find($quadraId);

            if ($quadra !== null) {
                $this->recalcularEstimativas($quadra);
                $this->chamarProximos($quadra->fresh());
            }
        }
    }

    public function alternarModoChuva(Tenant $tenant, bool $ativo): void
    {
        $tenant->update(['modo_chuva' => $ativo]);

        if ($ativo) {
            $quadras = Quadra::query()->where('coberta', false)->get();

            foreach ($quadras as $quadra) {
                $this->recalcularEstimativas($quadra);
            }
        }
    }

    /**
     * Recalcula posições e horários previstos de todos que aguardam
     * na fila de uma quadra, considerando a partida atual e o tempo extra.
     */
    public function recalcularEstimativas(Quadra $quadra): void
    {
        $tenant = $quadra->tenant;

        $partidaAtual = Partida::query()
            ->where('quadra_id', $quadra->id)
            ->where('status', PartidaStatus::EmAndamento)
            ->first();

        $cursor = $partidaAtual
            ? $partidaAtual->inicio_real->copy()->addMinutes($partidaAtual->duracao_minutos + $partidaAtual->tempo_extra)
            : now();

        $entradas = Fila::query()
            ->where('quadra_id', $quadra->id)
            ->where('status', FilaStatus::Aguardando)
            ->orderByDesc('prioridade')
            ->orderBy('posicao')
            ->get();

        $posicao = 1;
        $grupoAtual = collect();
        $necessarios = null;

        foreach ($entradas as $entrada) {
            $entrada->posicao = $posicao;
            $posicao++;

            $necessarios ??= $this->quantidadeDeJogadores($entrada->modalidade);
            $grupoAtual->push($entrada);
            $entrada->horario_estimado = $cursor->copy();

            if ($grupoAtual->count() >= $necessarios) {
                $cursor = $cursor->copy()->addMinutes($this->duracaoModalidade($tenant, $grupoAtual->first()->modalidade, $quadra));
                $grupoAtual = collect();
                $necessarios = null;
            }

            $entrada->save();
        }
    }

    private function duracaoModalidade(Tenant $tenant, Modalidade $modalidade, Quadra $quadra): int
    {
        if ($tenant->modo_chuva && ! $quadra->coberta) {
            return (int) ($this->config->get(ConfiguracaoChave::CHUVA_DURACAO_MINUTOS)['minutos'] ?? 40);
        }

        if ($tenant->modo_chuva) {
            return (int) ($this->config->get(ConfiguracaoChave::CHUVA_DURACAO_MINUTOS)['minutos'] ?? 40);
        }

        $chave = match ($modalidade) {
            Modalidade::Simples => ConfiguracaoChave::MODALIDADE_SIMPLES_MINUTOS,
            Modalidade::Duplas => ConfiguracaoChave::MODALIDADE_DUPLAS_MINUTOS,
            Modalidade::Octeto => ConfiguracaoChave::MODALIDADE_OCTETO_MINUTOS,
            Modalidade::Ranking => ConfiguracaoChave::MODALIDADE_RANKING_MINUTOS,
        };

        $padrao = Modalidade::Octeto === $modalidade ? 120 : 60;

        return (int) ($this->config->get($chave)['minutos'] ?? $padrao);
    }

    private function quantidadeDeJogadores(Modalidade $modalidade): int
    {
        return $modalidade->jogadores() ?? 4;
    }

    private function garantirQuadraDisponivelParaFila(Tenant $tenant, Quadra $quadra): void
    {
        if ($quadra->status === QuadraStatus::Manutencao) {
            throw ValidationException::withMessages(['quadra' => 'Quadra em manutenção.']);
        }

        if ($this->quadraBloqueadaAgora($quadra)) {
            throw ValidationException::withMessages(['quadra' => 'Quadra bloqueada neste horário.']);
        }

        if ($tenant->modo_chuva && ! $quadra->coberta) {
            throw ValidationException::withMessages(['quadra' => 'Modo chuva ativo: apenas quadras cobertas disponíveis.']);
        }
    }

    private function garantirSemDuplicidade(User $jogador): void
    {
        $emOutraFila = Fila::query()
            ->where('user_id', $jogador->id)
            ->whereIn('status', [FilaStatus::Aguardando, FilaStatus::Chamado, FilaStatus::Jogando])
            ->exists();

        if ($emOutraFila) {
            throw ValidationException::withMessages(['jogador' => 'Você já está em uma fila.']);
        }
    }

    private function garantirPresencaValidada(User $jogador): void
    {
        $presenteHoje = Presenca::query()
            ->where('user_id', $jogador->id)
            ->whereDate('validado_em', now()->toDateString())
            ->exists();

        if (! $presenteHoje) {
            throw ValidationException::withMessages(['jogador' => 'Confirme sua presença antes de entrar na fila.']);
        }
    }

    private function quadraBloqueadaAgora(Quadra $quadra): bool
    {
        return Bloqueio::query()
            ->where('quadra_id', $quadra->id)
            ->where('inicio', '<=', now())
            ->where('fim', '>', now())
            ->exists();
    }
}
