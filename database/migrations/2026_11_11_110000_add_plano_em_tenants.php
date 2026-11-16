<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Clube criado por conta própria: 30 dias grátis, depois precisa contratar.
            $table->boolean('teste_gratis')->default(false)->after('expira_em');
            // Quantos usuários de equipe (admin, recepção, professor) o clube pode ter. Vazio = sem limite.
            $table->unsignedSmallInteger('limite_equipe')->nullable()->after('teste_gratis');
            // Quantos sócios o clube pode ter. Vazio = sem limite.
            $table->unsignedInteger('limite_socios')->nullable()->after('limite_equipe');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['teste_gratis', 'limite_equipe', 'limite_socios']);
        });
    }
};
