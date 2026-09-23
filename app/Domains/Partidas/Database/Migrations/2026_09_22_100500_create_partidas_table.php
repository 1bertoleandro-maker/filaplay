<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('quadra_id')->constrained('quadras')->restrictOnDelete();
            $table->string('modalidade', 20);
            $table->unsignedSmallInteger('duracao_minutos');
            $table->dateTime('inicio_previsto')->nullable();
            $table->dateTime('inicio_real')->nullable();
            $table->dateTime('fim_real')->nullable();
            $table->unsignedSmallInteger('tempo_extra')->default(0);
            $table->string('status', 20)->default('agendada');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'quadra_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partidas');
    }
};
