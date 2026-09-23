<?php

declare(strict_types=1);

namespace App\Domains\Clube\Services;

use App\Domains\Clube\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class HorarioDoClube
{
    /**
     * @var array<string, string>
     */
    public const DIAS = [
        'segunda' => 'Segunda',
        'terca' => 'Terça',
        'quarta' => 'Quarta',
        'quinta' => 'Quinta',
        'sexta' => 'Sexta',
        'sabado' => 'Sábado',
        'domingo' => 'Domingo',
    ];

    public function chaveDia(CarbonInterface $data): string
    {
        return match ((int) $data->dayOfWeek) {
            Carbon::MONDAY => 'segunda',
            Carbon::TUESDAY => 'terca',
            Carbon::WEDNESDAY => 'quarta',
            Carbon::THURSDAY => 'quinta',
            Carbon::FRIDAY => 'sexta',
            Carbon::SATURDAY => 'sabado',
            default => 'domingo',
        };
    }

    /**
     * @return array{abre: Carbon, fecha: Carbon}|null
     */
    public function janela(Tenant $tenant, CarbonInterface $dia): ?array
    {
        $grade = $tenant->horario_funcionamento ?? [];
        $config = $grade[$this->chaveDia($dia)] ?? null;

        if (! is_array($config) || ($config['fechado'] ?? false)) {
            return null;
        }

        $abre = $config['abre'] ?? null;
        $fecha = $config['fecha'] ?? null;

        if (! is_string($abre) || ! is_string($fecha) || $abre === '' || $fecha === '') {
            return null;
        }

        $data = $dia->toDateString();

        return [
            'abre' => Carbon::parse($data.' '.$abre),
            'fecha' => Carbon::parse($data.' '.$fecha),
        ];
    }

    public function cabe(Tenant $tenant, CarbonInterface $inicio, CarbonInterface $fim): bool
    {
        $janela = $this->janela($tenant, $inicio);

        if ($janela === null) {
            return false;
        }

        return $inicio->greaterThanOrEqualTo($janela['abre'])
            && $fim->lessThanOrEqualTo($janela['fecha'])
            && $fim->greaterThan($inicio);
    }
}
