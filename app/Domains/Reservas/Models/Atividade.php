<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Models;

use App\Domains\Quadras\Models\Quadra;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Linha do tempo operacional do clube (reservas, entradas, chuva, perdas…).
 */
class Atividade extends Model
{
    use BelongsToTenant;

    protected $table = 'atividades';

    protected $fillable = [
        'tenant_id',
        'tipo',
        'titulo',
        'detalhe',
        'quadra_id',
        'user_id',
    ];

    public function quadra(): BelongsTo
    {
        return $this->belongsTo(Quadra::class);
    }
}
