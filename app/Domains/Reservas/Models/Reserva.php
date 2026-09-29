<?php

declare(strict_types=1);

namespace App\Domains\Reservas\Models;

use App\Domains\Jogadores\Models\User;
use App\Domains\Quadras\Models\Quadra;
use App\Domains\Reservas\Database\Factories\ReservaFactory;
use App\Domains\Reservas\Enums\ReservaOrigem;
use App\Domains\Reservas\Enums\ReservaStatus;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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
        'modalidade',
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

    public function jogadores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'reserva_user')
            ->withPivot(['tenant_id', 'ordem'])
            ->orderByPivot('ordem')
            ->withTimestamps();
    }

    /** Reservas que ainda valem na agenda (confirmadas e não canceladas). */
    public function scopeConfirmadas(Builder $query): Builder
    {
        return $query->where('status', ReservaStatus::Confirmada);
    }

    /**
     * Janela da agenda ao vivo: do “limite” (ex.: 2h atrás) em diante.
     * Evita carregar histórico antigo no painel / tablet / TV.
     */
    public function scopeNaAgenda(Builder $query, ?Carbon $desde = null): Builder
    {
        return $query
            ->confirmadas()
            ->where('fim', '>=', $desde ?? now()->subHours(2))
            ->orderBy('inicio');
    }

    /**
     * Sócio já está em alguma reserva confirmada que cruza o horário pedido
     * (qualquer quadra). Impede jogar em dois lugares ao mesmo tempo.
     */
    public static function conflitoDoSocio(
        User $socio,
        Carbon $inicio,
        Carbon $fim,
        ?int $excetoReservaId = null,
    ): ?self {
        return static::query()
            ->with('quadra')
            ->confirmadas()
            ->when($excetoReservaId !== null, fn (Builder $q): Builder => $q->where('id', '!=', $excetoReservaId))
            ->where('inicio', '<', $fim)
            ->where('fim', '>', $inicio)
            ->where(function (Builder $q) use ($socio): void {
                $q->where('user_id', $socio->id)
                    ->orWhereHas('jogadores', fn (Builder $j): Builder => $j->where('users.id', $socio->id));
            })
            ->orderBy('inicio')
            ->first();
    }

    public function mensagemConflitoSocio(User $socio): string
    {
        $quadra = $this->quadra?->apelido ?: $this->quadra?->nome ?: 'outra quadra';

        return sprintf(
            '%s ainda está na %s até %s. Só pode reservar de novo depois que o horário acabar.',
            $socio->primeiroNome(),
            $quadra,
            $this->fim->format('H:i'),
        );
    }

    /**
     * @return Collection<int, User>
     */
    public function elenco(): Collection
    {
        $elenco = $this->relationLoaded('jogadores') ? $this->jogadores : $this->jogadores()->get();

        if ($elenco->isNotEmpty()) {
            return $elenco;
        }

        return collect([$this->user])->filter();
    }
}
