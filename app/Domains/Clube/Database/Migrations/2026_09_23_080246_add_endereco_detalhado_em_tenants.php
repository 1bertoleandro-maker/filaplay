<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('cep', 9)->nullable()->after('endereco');
            $table->string('numero', 20)->nullable()->after('cep');
            $table->string('bairro')->nullable()->after('numero');
            $table->string('cidade')->nullable()->after('bairro');
            $table->string('estado', 2)->nullable()->after('cidade');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['cep', 'numero', 'bairro', 'cidade', 'estado']);
        });
    }
};
