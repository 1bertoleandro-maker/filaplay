<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reserva_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('reserva_id')->constrained('reservas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('ordem')->default(1);
            $table->timestamps();

            $table->unique(['reserva_id', 'user_id']);
        });

        $reservas = DB::table('reservas')->whereNull('deleted_at')->get(['id', 'tenant_id', 'user_id', 'created_at', 'updated_at']);

        foreach ($reservas as $reserva) {
            DB::table('reserva_user')->insert([
                'tenant_id' => $reserva->tenant_id,
                'reserva_id' => $reserva->id,
                'user_id' => $reserva->user_id,
                'ordem' => 1,
                'created_at' => $reserva->created_at,
                'updated_at' => $reserva->updated_at,
            ]);
        }

        Schema::table('reservas', function (Blueprint $table): void {
            $table->string('modalidade', 20)->nullable()->after('status');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('facial_token', 40)->nullable()->unique()->after('confirmacao_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('facial_token');
        });

        Schema::table('reservas', function (Blueprint $table): void {
            $table->dropColumn('modalidade');
        });

        Schema::dropIfExists('reserva_user');
    }
};
