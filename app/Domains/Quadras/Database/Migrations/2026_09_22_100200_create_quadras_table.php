<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quadras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('nome');
            $table->string('apelido')->nullable();
            $table->string('foto_path')->nullable();
            $table->string('tipo_piso', 20);
            $table->boolean('coberta')->default(false);
            $table->boolean('iluminacao')->default(false);
            $table->string('status', 20)->default('disponivel');
            $table->unsignedInteger('ordem_exibicao')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'nome']);
            $table->index(['tenant_id', 'ordem_exibicao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quadras');
    }
};
