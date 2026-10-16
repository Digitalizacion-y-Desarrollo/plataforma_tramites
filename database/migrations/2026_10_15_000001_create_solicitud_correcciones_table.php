<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_correcciones', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->uuid('id')->primary();
            $table->uuid('solicitud_id');
            $table->string('url_token_hash', 64)->unique();
            $table->string('password_hash', 255);
            $table->unsignedInteger('intentos')->default(0);
            $table->timestamp('bloqueada_at')->nullable();
            $table->timestamp('completada_at')->nullable();
            $table->string('actor_accesos_id', 100)->nullable();
            $table->string('actor_nombre_snapshot', 200)->nullable();
            $table->string('actor_departamento_snapshot', 200)->nullable();
            $table->timestamps();

            $table->index(['solicitud_id', 'completada_at']);
            $table->foreign('solicitud_id')->references('id')->on('solicitudes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_correcciones');
    }
};
