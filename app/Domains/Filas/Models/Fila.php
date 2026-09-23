<?php

declare(strict_types=1);

namespace App\Domains\Filas\Models;

use App\Domains\Filas\Database\Factories\FilaFactory;
use App\Domains\Filas\Enums\FilaStatus;
use App\Domains\Jogadores\Models\User;
use App\Domains\Partidas\Enums\Modalidade;
use App\Domains\Partidas\Models\Partida;
use App\Domains\Quadras\Models\Quadra;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fila extends Model
{
    /** @use HasFactory<FilaFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $table = 'filas';

    protected $fillable = [
        'tenant_id',
        'quadra_id',
        'user_id',
        'modalidade',
        'prioridade',
        'posicao',
        'horario_entrada',
        'horario_estimado',
        'status',
        'partida_id',
        'chamado_em',
        'confirmar_at',
    ];

    protected function casts(): array
    {
        return [
            'modalidade' => Modalidade::class,
            'prioridade' => 'integer',
            'posicao' => 'integer',
            'horario_entrada' => 'datetime',
            'horario_estimado' => 'datetime',
            'status' => FilaStatus::class,
            'chamado_em' => 'datetime',
            'confirmar_at' => 'datetime',
        ];
    }

    protected static function newFactory(): FilaFactory
    {
        return FilaFactory::new();
    }

    public function quadra(): BelongsTo
    {
        return $this->belongsTo(Quadra::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function partida(): BelongsTo
    {
        return $this->belongsTo(Partida::class);
    }

    public function segundosRestantesParaConfirmar(): int
    {
        if ($this->confirmar_at === null) {
            return 0;
        }

        return max(0, (int) now()->diffInSeconds($this->confirmar_at, false));
    }
}
