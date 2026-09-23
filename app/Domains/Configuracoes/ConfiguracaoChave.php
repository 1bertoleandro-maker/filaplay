<?php

declare(strict_types=1);

namespace App\Domains\Configuracoes;

final class ConfiguracaoChave
{
    public const MODALIDADE_SIMPLES_MINUTOS = 'modalidade.simples.duracao_minutos';

    public const MODALIDADE_DUPLAS_MINUTOS = 'modalidade.duplas.duracao_minutos';

    public const MODALIDADE_OCTETO_MINUTOS = 'modalidade.octeto.duracao_minutos';

    public const MODALIDADE_RANKING_MINUTOS = 'modalidade.ranking.duracao_minutos';

    public const NO_SHOW_SEGUNDOS = 'no_show.segundos';

    public const TEMPO_EXTRA_MINUTOS = 'tempo_extra.minutos';

    public const CHUVA_DURACAO_MINUTOS = 'chuva.duracao_minutos';

    public const CANCELAMENTO_ANTECEDENCIA_HORAS = 'reserva.cancelamento_antecedencia_horas';

    /**
     * @return array<string, array<string, int>>
     */
    public static function padroes(): array
    {
        return [
            self::MODALIDADE_SIMPLES_MINUTOS => ['minutos' => 60],
            self::MODALIDADE_DUPLAS_MINUTOS => ['minutos' => 60],
            self::MODALIDADE_OCTETO_MINUTOS => ['minutos' => 120],
            self::MODALIDADE_RANKING_MINUTOS => ['minutos' => 60],
            self::NO_SHOW_SEGUNDOS => ['segundos' => 300],
            self::TEMPO_EXTRA_MINUTOS => ['minutos' => 5],
            self::CHUVA_DURACAO_MINUTOS => ['minutos' => 40],
            self::CANCELAMENTO_ANTECEDENCIA_HORAS => ['horas' => 2],
        ];
    }
}
