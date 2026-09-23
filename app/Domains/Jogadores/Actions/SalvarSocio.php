<?php

declare(strict_types=1);

namespace App\Domains\Jogadores\Actions;

use App\Domains\Jogadores\Enums\NivelJogador;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Enums\UserStatus;
use App\Domains\Jogadores\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class SalvarSocio
{
    public function __construct(private readonly EnviarConviteAcesso $convite) {}

    public function handle(
        User $actor,
        array $dados,
        ?UploadedFile $foto = null,
        ?UploadedFile $face = null,
        ?User $socio = null,
    ): User {
        if ($socio === null) {
            abort_unless($actor->can('create', User::class), 403);
        } else {
            abort_unless($actor->can('update', $socio), 403);
        }

        $validados = Validator::make($dados, [
            'nome' => ['required', 'string', 'max:120'],
            'matricula' => [
                'required',
                'string',
                'max:32',
                Rule::unique('users', 'matricula')->where('tenant_id', $actor->tenant_id)->ignore($socio?->id),
            ],
            'telefone' => ['nullable', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($socio?->id)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'nivel' => ['nullable', Rule::enum(NivelJogador::class)],
        ])->validate();

        $papel = UserRole::from($validados['role']);

        if ($actor->role !== UserRole::Administrador && $papel === UserRole::Administrador) {
            throw ValidationException::withMessages([
                'role' => 'Somente o administrador cadastra outro administrador.',
            ]);
        }

        $ehNovo = $socio === null;

        if ($socio === null) {
            $socio = new User;
            $socio->tenant_id = $actor->tenant_id;
            $socio->password = Hash::make(Str::random(32));
            $socio->qr_token = (string) Str::uuid();
            $socio->bloqueado = false;

            if ($face !== null) {
                // Foto tirada na hora (webcam da recepção): já libera o acesso.
                $socio->status = UserStatus::Ativo;
                $socio->email_verified_at = now();
            } else {
                // Sem foto agora: o sócio confirma o cadastro sozinho pelo
                // link enviado por e-mail/WhatsApp, onde define a senha e a foto.
                $socio->status = UserStatus::Pendente;
                $socio->confirmacao_token = (string) Str::uuid();
                $socio->convite_enviado_em = now();
            }
        }

        if ($foto !== null) {
            Validator::make(['foto' => $foto], ['foto' => ['image', 'max:4096']])->validate();
        }

        if ($face !== null) {
            Validator::make(['face' => $face], ['face' => ['image', 'max:4096']])->validate();
        }

        if ($foto !== null) {
            if ($socio->foto_path) {
                Storage::disk('public')->delete($socio->foto_path);
            }

            $socio->foto_path = $foto->store('socios/'.$actor->tenant_id, 'public');
        }

        if ($face !== null) {
            if ($socio->face_photo_path) {
                Storage::disk('public')->delete($socio->face_photo_path);
            }

            $socio->face_photo_path = $face->store('faces/'.$actor->tenant_id, 'public');
            $socio->cadastro_facial_completo = true;
        }

        $socio->fill([
            'nome' => $validados['nome'],
            'matricula' => $validados['matricula'],
            'telefone' => $validados['telefone'] ?? null,
            'email' => $validados['email'],
            'role' => $papel,
            'nivel' => ($validados['nivel'] ?? null) !== null ? NivelJogador::from($validados['nivel']) : null,
        ])->save();

        if ($ehNovo && $socio->confirmacao_token !== null) {
            $this->convite->handle($socio, "Seu cadastro no {$socio->tenant->nome} foi criado.");
        }

        return $socio->refresh();
    }
}
