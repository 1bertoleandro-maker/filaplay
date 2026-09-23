<?php

declare(strict_types=1);

namespace App\Domains\Presenca\Models;

use App\Domains\Jogadores\Models\User;
use App\Domains\Presenca\Database\Factories\PresencaFactory;
use App\Domains\Presenca\Enums\PresencaMetodo;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presenca extends Model
{
    /** @use HasFactory<PresencaFactory> */
    use BelongsToTenant, HasFactory;

    protected $table = 'presencas';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'metodo',
        'latitude',
        'longitude',
        'validado_em',
    ];

    protected function casts(): array
    {
        return [
            'metodo' => PresencaMetodo::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'validado_em' => 'datetime',
        ];
    }

    protected static function newFactory(): PresencaFactory
    {
        return PresencaFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
