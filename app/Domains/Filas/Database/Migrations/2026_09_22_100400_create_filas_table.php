<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('quadra_id')->constrained('quadras')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedSmallInteger('prioridade')->default(0);
            $table->unsignedInteger('posicao');
            $table->dateTime('horario_entrada');
            $table->dateTime('horario_estimado')->nullable();
            $table->string('status', 20)->default('aguardando');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'quadra_id', 'posicao']);
            $table->index(['tenant_id', 'user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filas');
    }
};
