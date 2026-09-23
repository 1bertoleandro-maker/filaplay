<?php

declare(strict_types=1);

namespace App\Domains\Partidas\Models;

use App\Domains\Jogadores\Models\User;
use App\Domains\Partidas\Database\Factories\PartidaFactory;
use App\Domains\Partidas\Enums\Modalidade;
use App\Domains\Partidas\Enums\PartidaStatus;
use App\Domains\Quadras\Models\Quadra;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partida extends Model
{
    /** @use HasFactory<PartidaFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'quadra_id',
        'modalidade',
        'duracao_minutos',
        'inicio_previsto',
        'inicio_real',
        'fim_real',
        'tempo_extra',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'modalidade' => Modalidade::class,
            'duracao_minutos' => 'integer',
            'inicio_previsto' => 'datetime',
            'inicio_real' => 'datetime',
            'fim_real' => 'datetime',
            'tempo_extra' => 'integer',
            'status' => PartidaStatus::class,
        ];
    }

    protected static function newFactory(): PartidaFactory
    {
        return PartidaFactory::new();
    }

    public function quadra(): BelongsTo
    {
        return $this->belongsTo(Quadra::class);
    }

    public function jogadores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'partida_user')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }
}
