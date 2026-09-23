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
            $table->string('status', 20)->default('ativo')->after('nome');
            $table->dateTime('aprovado_em')->nullable()->after('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->boolean('super_admin')->default(false)->after('role');
            $table->string('confirmacao_token', 40)->nullable()->unique()->after('qr_token');
            $table->dateTime('convite_enviado_em')->nullable()->after('confirmacao_token');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['status', 'aprovado_em']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'super_admin', 'confirmacao_token', 'convite_enviado_em']);
        });
    }
};
