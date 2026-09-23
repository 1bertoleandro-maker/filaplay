<?php

declare(strict_types=1);

namespace App\Domains\Clube\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Busca rua, bairro, cidade e estado a partir de um CEP, usando o ViaCEP
 * (serviço público e gratuito, sem necessidade de chave de API).
 */
final class BuscarEnderecoPeloCep
{
    /**
     * @return array{logradouro: ?string, bairro: ?string, cidade: ?string, estado: ?string}|null
     */
    public function handle(string $cep): ?array
    {
        $cepLimpo = preg_replace('/\D/', '', $cep) ?? '';

        if (strlen($cepLimpo) !== 8) {
            return null;
        }

        try {
            $resposta = Http::timeout(6)->get("https://viacep.com.br/ws/{$cepLimpo}/json/");
        } catch (\Throwable $e) {
            Log::warning('Falha ao consultar ViaCEP', ['cep' => $cepLimpo, 'erro' => $e->getMessage()]);

            return null;
        }

        if (! $resposta->successful()) {
            return null;
        }

        $dados = $resposta->json();

        if (! is_array($dados) || ($dados['erro'] ?? false) === true) {
            return null;
        }

        return [
            'logradouro' => filled($dados['logradouro'] ?? null) ? $dados['logradouro'] : null,
            'bairro' => filled($dados['bairro'] ?? null) ? $dados['bairro'] : null,
            'cidade' => filled($dados['localidade'] ?? null) ? $dados['localidade'] : null,
            'estado' => filled($dados['uf'] ?? null) ? $dados['uf'] : null,
        ];
    }
}
