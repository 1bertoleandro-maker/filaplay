<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bloqueios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('quadra_id')->constrained('quadras')->restrictOnDelete();
            $table->string('tipo', 20);
            $table->dateTime('inicio');
            $table->dateTime('fim');
            $table->string('motivo')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'quadra_id', 'inicio', 'fim']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueios');
    }
};
