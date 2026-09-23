<?php

declare(strict_types=1);

namespace App\Domains\Ranking\Models;

use App\Domains\Jogadores\Models\User;
use App\Domains\Ranking\Database\Factories\RankingFactory;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ranking extends Model
{
    /** @use HasFactory<RankingFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'pontos',
        'vitorias',
        'partidas',
        'posicao',
        'atualizado_em',
    ];

    protected function casts(): array
    {
        return [
            'pontos' => 'integer',
            'vitorias' => 'integer',
            'partidas' => 'integer',
            'posicao' => 'integer',
            'atualizado_em' => 'datetime',
        ];
    }

    protected static function newFactory(): RankingFactory
    {
        return RankingFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
