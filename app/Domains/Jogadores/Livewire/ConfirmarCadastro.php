<?php

declare(strict_types=1);

namespace App\Domains\Jogadores\Livewire;

use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Enums\UserStatus;
use App\Domains\Jogadores\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.guest')]
class ConfirmarCadastro extends Component
{
    use WithFileUploads;

    public User $socio;

    public string $senha = '';

    public string $senha_confirmation = '';

    public mixed $foto = null;

    public function mount(string $token): void
    {
        $socio = User::query()->where('confirmacao_token', $token)->first();

        abort_if($socio === null, 404, 'Link inválido ou já usado.');

        $this->socio = $socio;
    }

    /**
     * Só exigimos a foto do rosto de quem vai jogar (reconhecimento facial
     * na quadra). Responsável de clube, recepção etc. podem confirmar só com senha.
     */
    public function precisaDeFoto(): bool
    {
        return $this->socio->role === UserRole::Jogador;
    }

    public function confirmar(): void
    {
        $this->validate([
            'senha' => ['required', 'string', 'min:8', 'same:senha_confirmation'],
            'foto' => [$this->precisaDeFoto() ? 'required' : 'nullable', 'image', 'max:4096'],
        ]);

        $foto = $this->foto;
        $caminho = null;

        if ($foto instanceof UploadedFile) {
            $caminho = $foto->store('faces/'.$this->socio->tenant_id, 'public');
        }

        $this->socio->forceFill([
            'password' => Hash::make($this->senha),
            'foto_path' => $this->socio->foto_path ?: $caminho,
            'face_photo_path' => $caminho ?? $this->socio->face_photo_path,
            'cadastro_facial_completo' => $caminho !== null,
            'status' => UserStatus::Ativo,
            'confirmacao_token' => null,
            'email_verified_at' => now(),
        ])->save();

        Auth::login($this->socio->fresh());
        Session::regenerate();

        $this->redirect(route('dashboard'), navigate: false);
    }

    public function render(): View
    {
        return view('domains.jogadores.confirmar-cadastro');
    }
}
