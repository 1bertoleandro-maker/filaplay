<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comunicacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('canal', 20);
            $table->string('destino');
            $table->string('assunto')->nullable();
            $table->text('corpo');
            $table->string('status', 20)->default('registrado');
            $table->dateTime('enviado_em')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'canal', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunicacoes');
    }
};
