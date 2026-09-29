<?php

declare(strict_types=1);

namespace App\Domains\Jogadores\Livewire;

use App\Domains\Jogadores\Enums\UserStatus;
use App\Domains\Jogadores\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.guest')]
class CadastrarFacial extends Component
{
    use WithFileUploads;

    public User $socio;

    public mixed $foto = null;

    public bool $concluido = false;

    public function mount(string $token): void
    {
        $socio = User::query()->where('facial_token', $token)->first();

        abort_if($socio === null, 404, 'Link inválido ou já usado.');

        $this->socio = $socio;
    }

    public function salvar(): void
    {
        $this->validate([
            'foto' => ['required', 'image', 'max:4096'],
        ]);

        $foto = $this->foto;
        abort_unless($foto instanceof UploadedFile, 422);

        $caminho = $foto->store('faces/'.$this->socio->tenant_id, 'public');

        $this->socio->forceFill([
            'face_photo_path' => $caminho,
            'foto_path' => $this->socio->foto_path ?: $caminho,
            'cadastro_facial_completo' => true,
            'facial_token' => null,
            'status' => UserStatus::Ativo,
            'email_verified_at' => $this->socio->email_verified_at ?? now(),
        ])->save();

        $this->concluido = true;
    }

    public function render(): View
    {
        return view('domains.jogadores.cadastrar-facial');
    }
}
