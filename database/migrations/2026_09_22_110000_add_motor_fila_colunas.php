<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('filas', function (Blueprint $table) {
            $table->string('modalidade', 20)->default('duplas')->after('user_id');
            $table->foreignId('partida_id')->nullable()->after('status')->constrained('partidas')->nullOnDelete();
            $table->dateTime('chamado_em')->nullable()->after('partida_id');
            $table->dateTime('confirmar_at')->nullable()->after('chamado_em');
        });

        Schema::table('bloqueios', function (Blueprint $table) {
            $table->boolean('recorrente')->default(false)->after('motivo');
        });
    }

    public function down(): void
    {
        Schema::table('filas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partida_id');
            $table->dropColumn(['modalidade', 'chamado_em', 'confirmar_at']);
        });

        Schema::table('bloqueios', function (Blueprint $table) {
            $table->dropColumn('recorrente');
        });
    }
};
