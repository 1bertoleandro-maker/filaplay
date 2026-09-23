<?php

declare(strict_types=1);

namespace App\Domains\Presenca\Actions;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Filas\Services\FilaEngine;
use App\Domains\Jogadores\Models\User;
use App\Domains\Presenca\Enums\PresencaMetodo;
use App\Domains\Presenca\Models\Presenca;
use Illuminate\Validation\ValidationException;

/**
 * Unifica os 4 métodos de confirmação de presença: Tablet (matrícula/código),
 * QR Code, GPS (geofence) e Recepção (manual).
 */
final class RegistrarPresenca
{
    public function __construct(private readonly FilaEngine $filaEngine) {}

    public function porMatricula(Tenant $tenant, string $matricula): User
    {
        $jogador = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('matricula', $matricula)
            ->first();

        if ($jogador === null) {
            throw ValidationException::withMessages(['matricula' => 'Matrícula não encontrada.']);
        }

        return $this->registrar($jogador, PresencaMetodo::Tablet);
    }

    public function porQrCode(Tenant $tenant, string $qrToken): User
    {
        $jogador = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('qr_token', $qrToken)
            ->first();

        if ($jogador === null) {
            throw ValidationException::withMessages(['qr_token' => 'QR Code inválido.']);
        }

        return $this->registrar($jogador, PresencaMetodo::QrCode);
    }

    public function porGps(User $jogador, float $latitude, float $longitude): User
    {
        $tenant = $jogador->tenant;

        if ($tenant->latitude === null || $tenant->longitude === null || $tenant->raio_gps_metros === null) {
            throw ValidationException::withMessages(['gps' => 'O clube ainda não configurou a localização.']);
        }

        $distancia = $this->distanciaMetros(
            (float) $tenant->latitude,
            (float) $tenant->longitude,
            $latitude,
            $longitude,
        );

        if ($distancia > $tenant->raio_gps_metros) {
            throw ValidationException::withMessages(['gps' => 'Você precisa estar no clube para confirmar presença.']);
        }

        return $this->registrar($jogador, PresencaMetodo::Gps, $latitude, $longitude);
    }

    public function porRecepcao(User $jogador): User
    {
        return $this->registrar($jogador, PresencaMetodo::Tablet);
    }

    private function registrar(User $jogador, PresencaMetodo $metodo, ?float $lat = null, ?float $lng = null): User
    {
        if ($jogador->bloqueado) {
            throw ValidationException::withMessages(['jogador' => 'Este sócio está bloqueado.']);
        }

        Presenca::query()->create([
            'tenant_id' => $jogador->tenant_id,
            'user_id' => $jogador->id,
            'metodo' => $metodo,
            'latitude' => $lat,
            'longitude' => $lng,
            'validado_em' => now(),
        ]);

        $this->filaEngine->confirmarChamada($jogador);

        return $jogador;
    }

    /**
     * Fórmula de Haversine para distância em metros entre duas coordenadas.
     */
    private function distanciaMetros(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $raioTerra = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $raioTerra * $c;
    }
}
