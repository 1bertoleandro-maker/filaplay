<?php

declare(strict_types=1);

namespace App\Domains\Clube\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Converte um endereço em latitude/longitude usando o Nominatim (OpenStreetMap),
 * serviço público e gratuito de geocodificação, sem necessidade de chave de API.
 */
final class GeocodificarEndereco
{
    /**
     * @return array{latitude: float, longitude: float}|null
     */
    public function handle(string $enderecoCompleto): ?array
    {
        if (trim($enderecoCompleto) === '') {
            return null;
        }

        try {
            $resposta = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'FilaPlay/1.0 (contato@filaplay.app)'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $enderecoCompleto.', Brasil',
                    'format' => 'json',
                    'countrycodes' => 'br',
                    'limit' => 1,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Falha ao geocodificar endereço', ['endereco' => $enderecoCompleto, 'erro' => $e->getMessage()]);

            return null;
        }

        if (! $resposta->successful()) {
            return null;
        }

        $dados = $resposta->json();

        if (! is_array($dados) || ! isset($dados[0]['lat'], $dados[0]['lon'])) {
            return null;
        }

        return [
            'latitude' => (float) $dados[0]['lat'],
            'longitude' => (float) $dados[0]['lon'],
        ];
    }
}
