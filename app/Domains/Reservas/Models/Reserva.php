<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Models;

use App\Domains\Jogadores\Models\User;
use App\Domains\Quadras\Models\Quadra;
use App\Domains\Reservas\Database\Factories\ReservaFactory;
use App\Domains\Reservas\Enums\ReservaOrigem;
use App\Domains\Reservas\Enums\ReservaStatus;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reserva extends Model
{
    /** @use HasFactory<ReservaFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'quadra_id',
        'user_id',
        'inicio',
        'fim',
        'origem',
        'status',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'inicio' => 'datetime',
            'fim' => 'datetime',
            'origem' => ReservaOrigem::class,
            'status' => ReservaStatus::class,
        ];
    }

    protected static function newFactory(): ReservaFactory
    {
        return ReservaFactory::new();
    }

    public function quadra(): BelongsTo
    {
        return $this->belongsTo(Quadra::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
