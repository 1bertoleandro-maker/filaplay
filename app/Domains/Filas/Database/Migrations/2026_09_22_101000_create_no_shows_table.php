<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('no_shows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('quadra_id')->nullable()->constrained('quadras')->nullOnDelete();
            $table->foreignId('fila_id')->nullable()->constrained('filas')->nullOnDelete();
            $table->dateTime('registrado_em');
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'registrado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('no_shows');
    }
};
