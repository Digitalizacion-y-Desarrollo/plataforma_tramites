<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canales_ayuda', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->json('preguntas_frecuentes')->nullable();
            $table->string('horario_atencion', 500)->nullable();
            $table->string('telefono_contacto', 50)->nullable();
            $table->string('email_contacto', 191)->nullable();
            $table->json('redes_sociales')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canales_ayuda');
    }
};
