<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Livewire;

use App\Domains\Clube\Services\HorarioDoClube;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Models\User;
use App\Domains\Quadras\Models\Bloqueio;
use App\Domains\Quadras\Models\Quadra;
use App\Domains\Reservas\Actions\CancelarReserva;
use App\Domains\Reservas\Actions\CriarReserva;
use App\Domains\Reservas\Enums\ReservaStatus;
use App\Domains\Reservas\Models\Reserva;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Tela de marcação de horário: uma grade visual (quadra x horário) em que
 * basta clicar num quadrinho livre para reservar. É a tela mais usada do
 * dia a dia, por isso o foco total em clareza — cores grandes, poucos
 * cliques e sem formulários longos.
 */
#[Layout('layouts.app')]
class ListaReservas extends Component
{
    use WithFileUploads;

    public string $data = '';

    public bool $formAberto = false;

    public ?int $quadra_id = null;

    public ?int $user_id = null;

    public string $hora = '';

    public string $validacao = 'secretaria';

    public mixed $foto_facial = null;

    public ?int $reservaSelecionadaId = null;

    public function mount(): void
    {
        abort_unless(in_array(auth()->user()?->role, [
            UserRole::Administrador,
            UserRole::Recepcao,
        ], true), 403);

        $this->data = now()->toDateString();
    }

    public function diaAnterior(): void
    {
        $this->data = Carbon::parse($this->data)->subDay()->toDateString();
    }

    public function diaSeguinte(): void
    {
        $this->data = Carbon::parse($this->data)->addDay()->toDateString();
    }

    public function hoje(): void
    {
        $this->data = now()->toDateString();
    }

    public function abrirSlot(int $quadraId, string $hora): void
    {
        $this->quadra_id = $quadraId;
        $this->hora = $hora;
        $this->user_id = null;
        $this->validacao = 'secretaria';
        $this->foto_facial = null;
        $this->resetValidation();
        $this->formAberto = true;
    }

    public function nova(): void
    {
        $this->abrirSlot(
            (int) (Quadra::query()->orderBy('ordem_exibicao')->value('id') ?? 0),
            now()->addHour()->format('H:00'),
        );
    }

    public function salvar(CriarReserva $action): void
    {
        $foto = $this->foto_facial instanceof UploadedFile ? $this->foto_facial : null;

        $action->handle(
            auth()->user(),
            (int) $this->quadra_id,
            (int) $this->user_id,
            $this->data,
            $this->hora,
            60,
            $this->validacao,
            $foto,
        );

        $this->formAberto = false;
        $this->foto_facial = null;
        session()->flash('status', 'Reserva confirmada!');
    }

    public function fechar(): void
    {
        $this->formAberto = false;
        $this->foto_facial = null;
        $this->resetValidation();
    }

    public function verDetalhe(int $reservaId): void
    {
        $this->reservaSelecionadaId = $reservaId;
    }

    public function fecharDetalhe(): void
    {
        $this->reservaSelecionadaId = null;
    }

    public function cancelar(CancelarReserva $action, int $reservaId): void
    {
        $reserva = Reserva::query()->findOrFail($reservaId);
        $action->handle(auth()->user(), $reserva);

        $this->reservaSelecionadaId = null;
        session()->flash('status', 'Reserva cancelada.');
    }

    public function render(HorarioDoClube $horario): View
    {
        $tenant = auth()->user()->tenant;
        $dia = Carbon::parse($this->data !== '' ? $this->data : now()->toDateString());
        $janela = $horario->janela($tenant, $dia);
        $slots = [];

        if ($janela !== null) {
            $cursor = $janela['abre']->copy();
            while ($cursor->copy()->addMinutes(60)->lessThanOrEqualTo($janela['fecha'])) {
                $slots[] = $cursor->format('H:i');
                $cursor->addMinutes(60);
            }
        }

        $quadras = Quadra::query()->orderBy('ordem_exibicao')->get();

        $reservas = Reserva::query()
            ->with('user')
            ->whereDate('inicio', $dia->toDateString())
            ->where('status', '!=', ReservaStatus::Cancelada)
            ->get()
            ->groupBy(fn (Reserva $r): string => $r->quadra_id.'|'.$r->inicio->format('H:i'));

        $bloqueios = Bloqueio::query()
            ->whereDate('inicio', '<=', $dia->toDateString())
            ->whereDate('fim', '>=', $dia->toDateString())
            ->get();

        $grade = [];

        foreach ($quadras as $quadra) {
            foreach ($slots as $slot) {
                $inicioSlot = Carbon::parse($dia->toDateString().' '.$slot);
                $fimSlot = $inicioSlot->copy()->addHour();
                $chave = $quadra->id.'|'.$slot;

                $reserva = $reservas->get($chave)?->first();

                $bloqueio = $bloqueios->first(function (Bloqueio $b) use ($quadra, $inicioSlot, $fimSlot): bool {
                    return $b->quadra_id === $quadra->id
                        && $b->inicio->lessThan($fimSlot)
                        && $b->fim->greaterThan($inicioSlot);
                });

                $status = 'livre';
                if ($reserva !== null) {
                    $status = 'ocupada';
                } elseif ($bloqueio !== null) {
                    $status = 'bloqueada';
                } elseif ($inicioSlot->lessThan(now())) {
                    $status = 'passado';
                }

                $grade[$quadra->id][$slot] = [
                    'status' => $status,
                    'reserva' => $reserva,
                    'bloqueio' => $bloqueio,
                ];
            }
        }

        return view('domains.reservas.lista-reservas', [
            'quadras' => $quadras,
            'slots' => $slots,
            'grade' => $grade,
            'fechado' => $janela === null,
            'socios' => User::query()->where('bloqueado', false)->orderBy('nome')->get(),
            'reservaSelecionada' => $this->reservaSelecionadaId
                ? Reserva::query()->with(['quadra', 'user'])->find($this->reservaSelecionadaId)
                : null,
            'diaLabel' => $dia->isToday() ? 'Hoje' : ($dia->isTomorrow() ? 'Amanhã' : $dia->translatedFormat('D, d/m')),
        ]);
    }
}
