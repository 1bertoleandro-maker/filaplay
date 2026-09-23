<?php

declare(strict_types=1);

namespace App\Domains\Filas\Models;

use App\Domains\Filas\Database\Factories\NoShowFactory;
use App\Domains\Jogadores\Models\User;
use App\Domains\Quadras\Models\Quadra;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoShow extends Model
{
    /** @use HasFactory<NoShowFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'quadra_id',
        'fila_id',
        'registrado_em',
    ];

    protected function casts(): array
    {
        return [
            'registrado_em' => 'datetime',
        ];
    }

    protected static function newFactory(): NoShowFactory
    {
        return NoShowFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quadra(): BelongsTo
    {
        return $this->belongsTo(Quadra::class);
    }

    public function fila(): BelongsTo
    {
        return $this->belongsTo(Fila::class);
    }
}
