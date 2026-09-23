<?php

declare(strict_types=1);

namespace App\Domains\Jogadores\Database\Factories;

use App\Domains\Clube\Models\Tenant;
use App\Domains\Jogadores\Enums\NivelJogador;
use App\Domains\Jogadores\Enums\UserRole;
use App\Domains\Jogadores\Enums\UserStatus;
use App\Domains\Jogadores\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'matricula' => fake()->unique()->numerify('MAT####'),
            'nome' => fake()->name(),
            'telefone' => fake()->numerify('(11) 9####-####'),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => UserStatus::Ativo,
            'nivel' => NivelJogador::Intermediario,
            'bloqueado' => false,
            'cadastro_facial_completo' => false,
            'role' => UserRole::Jogador,
            'qr_token' => (string) Str::uuid(),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => [
            'email_verified_at' => null,
        ]);
    }

    public function administrador(): static
    {
        return $this->state(fn (): array => [
            'role' => UserRole::Administrador,
        ]);
    }

    public function recepcao(): static
    {
        return $this->state(fn (): array => [
            'role' => UserRole::Recepcao,
        ]);
    }

    public function bloqueado(): static
    {
        return $this->state(fn (): array => [
            'bloqueado' => true,
        ]);
    }
}
