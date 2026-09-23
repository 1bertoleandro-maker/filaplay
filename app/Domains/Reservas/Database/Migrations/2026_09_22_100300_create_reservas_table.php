<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('quadra_id')->constrained('quadras')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('inicio');
            $table->dateTime('fim');
            $table->string('origem', 20);
            $table->string('status', 20)->default('confirmada');
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'quadra_id', 'inicio', 'fim']);
            $table->index(['tenant_id', 'user_id', 'inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
