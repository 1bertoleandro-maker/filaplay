<?php

declare(strict_types=1);

namespace App\Domains\Clube\Actions;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Clube\Services\HorarioDoClube;
use App\Domains\Jogadores\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class AtualizarClube
{
    public function handle(User $actor, Tenant $tenant, array $dados, ?UploadedFile $logo = null): Tenant
    {
        abort_unless($actor->can('update', $tenant), 403);

        $validados = Validator::make($dados, [
            'nome' => ['required', 'string', 'max:120'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'cep' => ['nullable', 'string', 'max:9'],
            'numero' => ['nullable', 'string', 'max:20'],
            'bairro' => ['nullable', 'string', 'max:120'],
            'cidade' => ['nullable', 'string', 'max:120'],
            'estado' => ['nullable', 'string', 'max:2'],
            'telefone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'raio_gps_metros' => ['required', 'integer', 'min:20', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'exibir_logo' => ['sometimes', 'boolean'],
            'horarios' => ['required', 'array'],
        ])->validate();

        $horarios = [];

        foreach (array_keys(HorarioDoClube::DIAS) as $dia) {
            $item = $dados['horarios'][$dia] ?? [];
            $fechado = filter_var($item['fechado'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($fechado) {
                $horarios[$dia] = ['fechado' => true, 'abre' => null, 'fecha' => null];

                continue;
            }

            $abre = (string) ($item['abre'] ?? '');
            $fecha = (string) ($item['fecha'] ?? '');

            if (! preg_match('/^\d{2}:\d{2}$/', $abre) || ! preg_match('/^\d{2}:\d{2}$/', $fecha) || $abre >= $fecha) {
                throw ValidationException::withMessages([
                    "horarios.$dia.fecha" => 'Informe abertura e fechamento, com o fechamento depois da abertura.',
                ]);
            }

            $horarios[$dia] = ['fechado' => false, 'abre' => $abre, 'fecha' => $fecha];
        }

        $validados['horarios'] = $horarios;

        if ($logo !== null) {
            Validator::make(['logo' => $logo], [
                'logo' => ['image', 'max:4096'],
            ])->validate();

            if ($tenant->logo) {
                Storage::disk('public')->delete($tenant->logo);
            }

            $validados['logo'] = $logo->store('clubes/'.$tenant->id, 'public');
        }

        $tenant->fill([
            'nome' => $validados['nome'],
            'endereco' => $validados['endereco'] ?? null,
            'cep' => $validados['cep'] ?? null,
            'numero' => $validados['numero'] ?? null,
            'bairro' => $validados['bairro'] ?? null,
            'cidade' => $validados['cidade'] ?? null,
            'estado' => $validados['estado'] ?? null,
            'telefone' => $validados['telefone'] ?? null,
            'email' => $validados['email'] ?? null,
            'raio_gps_metros' => $validados['raio_gps_metros'],
            'latitude' => $validados['latitude'] ?? null,
            'longitude' => $validados['longitude'] ?? null,
            'horario_funcionamento' => $validados['horarios'],
            'logo' => $validados['logo'] ?? $tenant->logo,
            'exibir_logo' => (bool) ($validados['exibir_logo'] ?? $tenant->exibir_logo),
        ])->save();

        return $tenant->refresh();
    }
}
