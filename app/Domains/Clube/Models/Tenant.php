<?php

declare(strict_types=1);

namespace App\Domains\Clube\Models;

use App\Domains\Clube\Database\Factories\TenantFactory;
use App\Domains\Clube\Enums\TenantStatus;
use App\Domains\Comunicacao\Models\Comunicacao;
use App\Domains\Configuracoes\Models\Configuracao;
use App\Domains\Filas\Models\Fila;
use App\Domains\Filas\Models\NoShow;
use App\Domains\Jogadores\Models\User;
use App\Domains\Partidas\Models\Partida;
use App\Domains\Presenca\Models\Presenca;
use App\Domains\Quadras\Models\Bloqueio;
use App\Domains\Quadras\Models\Quadra;
use App\Domains\Ranking\Models\Ranking;
use App\Domains\Reservas\Models\Reserva;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nome',
        'status',
        'aprovado_em',
        'expira_em',
        'logo',
        'exibir_logo',
        'endereco',
        'cep',
        'numero',
        'bairro',
        'cidade',
        'estado',
        'latitude',
        'longitude',
        'raio_gps_metros',
        'telefone',
        'email',
        'horario_funcionamento',
        'modo_chuva',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'aprovado_em' => 'datetime',
            'expira_em' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'raio_gps_metros' => 'integer',
            'horario_funcionamento' => 'array',
            'modo_chuva' => 'boolean',
            'exibir_logo' => 'boolean',
        ];
    }

    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function quadras(): HasMany
    {
        return $this->hasMany(Quadra::class);
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

    public function presencas(): HasMany
    {
        return $this->hasMany(Presenca::class);
    }

    public function configuracoes(): HasMany
    {
        return $this->hasMany(Configuracao::class);
    }

    public function bloqueios(): HasMany
    {
        return $this->hasMany(Bloqueio::class);
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(Ranking::class);
    }

    public function comunicacoes(): HasMany
    {
        return $this->hasMany(Comunicacao::class);
    }

    public function noShows(): HasMany
    {
        return $this->hasMany(NoShow::class);
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->logo
            ? Storage::disk('public')->url($this->logo)
            : null);
    }

    public function exibeLogoNoAmbiente(): bool
    {
        return $this->exibir_logo && filled($this->logoUrl);
    }

    public function expirado(): bool
    {
        return $this->expira_em !== null && $this->expira_em->isPast();
    }

    public function enderecoCompleto(): string
    {
        return implode(', ', array_filter([
            $this->endereco,
            $this->numero,
            $this->bairro,
            $this->cidade !== null && $this->estado !== null ? "{$this->cidade} - {$this->estado}" : $this->cidade,
        ], fn (?string $parte): bool => filled($parte)));
    }
}
