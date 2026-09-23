<?php

declare(strict_types=1);

namespace App\Domains\Comunicacao\Models;

use App\Domains\Comunicacao\Database\Factories\ComunicacaoFactory;
use App\Domains\Comunicacao\Enums\ComunicacaoCanal;
use App\Domains\Comunicacao\Enums\ComunicacaoStatus;
use App\Domains\Jogadores\Models\User;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comunicacao extends Model
{
    /** @use HasFactory<ComunicacaoFactory> */
    use BelongsToTenant, HasFactory;

    protected $table = 'comunicacoes';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'canal',
        'destino',
        'assunto',
        'corpo',
        'status',
        'enviado_em',
    ];

    protected function casts(): array
    {
        return [
            'canal' => ComunicacaoCanal::class,
            'status' => ComunicacaoStatus::class,
            'enviado_em' => 'datetime',
        ];
    }

    protected static function newFactory(): ComunicacaoFactory
    {
        return ComunicacaoFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
