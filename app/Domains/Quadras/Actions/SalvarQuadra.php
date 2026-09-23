<?php

declare(strict_types=1);

namespace App\Domains\Quadras\Actions;

use App\Domains\Jogadores\Models\User;
use App\Domains\Quadras\Enums\QuadraStatus;
use App\Domains\Quadras\Enums\TipoPiso;
use App\Domains\Quadras\Models\Quadra;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class SalvarQuadra
{
    public function handle(User $actor, array $dados, ?UploadedFile $foto = null, ?Quadra $quadra = null): Quadra
    {
        if ($quadra === null) {
            abort_unless($actor->can('create', Quadra::class), 403);
        } else {
            abort_unless($actor->can('update', $quadra), 403);
        }

        $validados = Validator::make($dados, [
            'nome' => ['required', 'string', 'max:80'],
            'apelido' => ['nullable', 'string', 'max:40'],
            'tipo_piso' => ['required', Rule::enum(TipoPiso::class)],
            'coberta' => ['boolean'],
            'iluminacao' => ['boolean'],
            'status' => ['required', Rule::enum(QuadraStatus::class)],
            'ordem_exibicao' => ['required', 'integer', 'min:1', 'max:99'],
        ])->validate();

        if ($foto !== null) {
            Validator::make(['foto' => $foto], [
                'foto' => ['image', 'max:4096'],
            ])->validate();
        }

        $quadra ??= new Quadra(['tenant_id' => $actor->tenant_id]);

        if ($foto !== null) {
            if ($quadra->foto_path) {
                Storage::disk('public')->delete($quadra->foto_path);
            }

            $quadra->foto_path = $foto->store('quadras/'.$actor->tenant_id, 'public');
        }

        $quadra->fill([
            'nome' => $validados['nome'],
            'apelido' => $validados['apelido'] ?? null,
            'tipo_piso' => $validados['tipo_piso'],
            'coberta' => (bool) ($validados['coberta'] ?? false),
            'iluminacao' => (bool) ($validados['iluminacao'] ?? false),
            'status' => $validados['status'],
            'ordem_exibicao' => $validados['ordem_exibicao'],
        ])->save();

        return $quadra->refresh();
    }
}
