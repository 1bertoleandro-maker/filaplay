<?php

declare(strict_types=1);

namespace App\Domains\Quadras\Models;

use App\Domains\Filas\Models\Fila;
use App\Domains\Partidas\Models\Partida;
use App\Domains\Quadras\Database\Factories\QuadraFactory;
use App\Domains\Quadras\Enums\QuadraStatus;
use App\Domains\Quadras\Enums\TipoPiso;
use App\Domains\Reservas\Models\Reserva;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Quadra extends Model
{
    /** @use HasFactory<QuadraFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'nome',
        'apelido',
        'foto_path',
        'tipo_piso',
        'coberta',
        'iluminacao',
        'status',
        'ordem_exibicao',
    ];

    protected function casts(): array
    {
        return [
            'tipo_piso' => TipoPiso::class,
            'coberta' => 'boolean',
            'iluminacao' => 'boolean',
            'status' => QuadraStatus::class,
            'ordem_exibicao' => 'integer',
        ];
    }

    protected static function newFactory(): QuadraFactory
    {
        return QuadraFactory::new();
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    public function filas(): HasMany
    {
        return $this->hasMany(Fila::class);
    }

    public function partidas(): HasMany
    {
        return $this->hasMany(Partida::class);
    }

    public function bloqueios(): HasMany
    {
        return $this->hasMany(Bloqueio::class);
    }

    public static function ilustracaoDoPiso(TipoPiso|string|null $piso): string
    {
        $chave = $piso instanceof TipoPiso ? $piso->value : (string) ($piso ?: 'saibro');

        if (! in_array($chave, ['saibro', 'duro', 'grama', 'areia'], true)) {
            $chave = 'saibro';
        }

        return asset('images/quadras/'.$chave.'.svg');
    }

    protected function fotoUrl(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->foto_path) {
                return Storage::disk('public')->url($this->foto_path);
            }

            return self::ilustracaoDoPiso($this->tipo_piso);
        });
    }
}
