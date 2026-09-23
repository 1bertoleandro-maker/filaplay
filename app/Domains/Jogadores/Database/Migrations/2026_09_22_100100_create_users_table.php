<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('matricula', 32);
            $table->string('nome');
            $table->string('telefone', 32)->nullable();
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('foto_path')->nullable();
            $table->string('face_photo_path')->nullable();
            $table->string('status', 20)->default('ativo');
            $table->string('nivel', 20)->nullable();
            $table->boolean('bloqueado')->default(false);
            $table->boolean('cadastro_facial_completo')->default(false);
            $table->string('role', 20);
            $table->uuid('qr_token')->unique();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'matricula']);
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
