<?php

declare(strict_types=1);

namespace App\Domains\Configuracoes\Models;

use App\Domains\Configuracoes\Database\Factories\ConfiguracaoFactory;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuracao extends Model
{
    /** @use HasFactory<ConfiguracaoFactory> */
    use BelongsToTenant, HasFactory;

    protected $table = 'configuracoes';

    protected $fillable = [
        'tenant_id',
        'chave',
        'valor',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'array',
        ];
    }

    protected static function newFactory(): ConfiguracaoFactory
    {
        return ConfiguracaoFactory::new();
    }
}
