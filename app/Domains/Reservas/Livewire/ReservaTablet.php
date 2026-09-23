<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Livewire;

use App\Domains\Clube\Services\HorarioDoClube;
use App\Domains\Quadras\Models\Quadra;
use App\Domains\Reservas\Actions\ReservarPelaTablet;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.kiosk')]
class ReservaTablet extends Component
{
    public string $mensagem = '';

    public bool $erro = false;

    public function reservar(
        ReservarPelaTablet $action,
        int $quadraId,
        string $hora,
        string $matriculaPrincipal,
        string $matriculaParceiro,
    ): void {
        try {
            $quadra = Quadra::query()->findOrFail($quadraId);

            $reserva = $action->handle(
                $quadra,
                $matriculaPrincipal,
                $matriculaParceiro !== '' ? $matriculaParceiro : null,
                now()->toDateString(),
                $hora,
            );

            $this->mensagem = 'Reserva confirmada na '.$reserva->quadra->nome.' às '.$reserva->inicio->format('H:i').'!';
            $this->erro = false;
        } catch (ValidationException $e) {
            $this->mensagem = (string) collect($e->errors())->flatten()->first();
            $this->erro = true;
        }
    }

    public function limpar(): void
    {
        $this->mensagem = '';
        $this->erro = false;
    }

    public function render(HorarioDoClube $horario): View
    {
        $tenant = auth()->user()->tenant;
        $dia = Carbon::today();
        $janela = $horario->janela($tenant, $dia);
        $slots = [];

        if ($janela !== null) {
            $cursor = $janela['abre']->copy();

            while ($cursor->copy()->addMinutes(60)->lessThanOrEqualTo($janela['fecha'])) {
                if ($cursor->greaterThan(now())) {
                    $slots[] = $cursor->format('H:i');
                }

                $cursor->addMinutes(60);
            }
        }

        return view('domains.reservas.reserva-tablet', [
            'quadras' => Quadra::query()->orderBy('ordem_exibicao')->get(),
            'slots' => $slots,
        ]);
    }
}
