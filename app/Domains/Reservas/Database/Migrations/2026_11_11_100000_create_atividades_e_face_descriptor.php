<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atividades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('tipo', 40);
            $table->string('titulo', 160);
            $table->string('detalhe', 255)->nullable();
            $table->foreignId('quadra_id')->nullable()->constrained('quadras')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'id']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->text('face_descriptor')->nullable()->after('face_photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('face_descriptor');
        });

        Schema::dropIfExists('atividades');
    }
};
