<?php

declare(strict_types=1);

namespace App\Domains\Quadras\Models;

use App\Domains\Quadras\Database\Factories\BloqueioFactory;
use App\Domains\Quadras\Enums\BloqueioTipo;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bloqueio extends Model
{
    /** @use HasFactory<BloqueioFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'quadra_id',
        'tipo',
        'inicio',
        'fim',
        'motivo',
        'recorrente',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => BloqueioTipo::class,
            'inicio' => 'datetime',
            'fim' => 'datetime',
            'recorrente' => 'boolean',
        ];
    }

    public function ativoAgora(): bool
    {
        return $this->inicio->lessThanOrEqualTo(now()) && $this->fim->greaterThan(now());
    }

    protected static function newFactory(): BloqueioFactory
    {
        return BloqueioFactory::new();
    }

    public function quadra(): BelongsTo
    {
        return $this->belongsTo(Quadra::class);
    }
}
