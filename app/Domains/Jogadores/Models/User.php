<?php

declare(strict_types=1);

namespace App\Domains\Jogadores\Models;

use App\Domains\Filas\Models\Fila;
use App\Domains\Filas\Models\NoShow;
use App\Domains\Jogadores\Database\Factories\UserFactory;
use App\Domains\Jogadores\Enums\NivelJogador;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Enums\UserStatus;
use App\Domains\Partidas\Models\Partida;
use App\Domains\Presenca\Models\Presenca;
use App\Domains\Ranking\Models\Ranking;
use App\Domains\Reservas\Models\Reserva;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use BelongsToTenant, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'matricula',
        'nome',
        'name',
        'telefone',
        'email',
        'google_id',
        'password',
        'foto_path',
        'face_photo_path',
        'status',
        'nivel',
        'bloqueado',
        'cadastro_facial_completo',
        'role',
        'super_admin',
        'qr_token',
        'confirmacao_token',
        'facial_token',
        'convite_enviado_em',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'bloqueado' => 'boolean',
            'cadastro_facial_completo' => 'boolean',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'nivel' => NivelJogador::class,
            'super_admin' => 'boolean',
            'convite_enviado_em' => 'datetime',
        ];
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) ($this->attributes['nome'] ?? ''),
            set: fn (string $value): array => ['nome' => $value],
        );
    }

    public function podeAcessar(): bool
    {
        return ! $this->bloqueado && $this->status === UserStatus::Ativo;
    }

    /** Sócio jogador ativo (ou com facial já liberado) pode reservar no tablet. */
    public function podeReservarNoTablet(): bool
    {
        if ($this->role !== UserRole::Jogador || $this->bloqueado) {
            return false;
        }

        if ($this->status === UserStatus::Ativo) {
            return true;
        }

        // Pendente com facial na secretaria: libera e ativa na hora.
        return $this->status === UserStatus::Pendente && $this->cadastro_facial_completo;
    }

    public function primeiroNome(): string
    {
        $parte = explode(' ', trim($this->nome))[0] ?? $this->nome;

        return $parte !== '' ? $parte : $this->nome;
    }

    public function iniciais(): string
    {
        $partes = preg_split('/\s+/', trim($this->nome)) ?: [];
        $letras = '';

        foreach (array_slice($partes, 0, 2) as $parte) {
            if ($parte !== '') {
                $letras .= mb_strtoupper(mb_substr($parte, 0, 1));
            }
        }

        return $letras !== '' ? $letras : '?';
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    public function filas(): HasMany
    {
        return $this->hasMany(Fila::class);
    }

    public function presencas(): HasMany
    {
        return $this->hasMany(Presenca::class);
    }

    public function partidas(): BelongsToMany
    {
        return $this->belongsToMany(Partida::class, 'partida_user')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    public function ranking(): HasOne
    {
        return $this->hasOne(Ranking::class);
    }

    public function noShows(): HasMany
    {
        return $this->hasMany(NoShow::class);
    }

    protected function fotoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->foto_path
            ? Storage::disk('public')->url($this->foto_path)
            : null);
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            $caminho = $this->foto_path ?: $this->face_photo_path;

            return $caminho ? Storage::disk('public')->url($caminho) : null;
        });
    }

    protected function faceUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->face_photo_path
            ? Storage::disk('public')->url($this->face_photo_path)
            : null);
    }
}
