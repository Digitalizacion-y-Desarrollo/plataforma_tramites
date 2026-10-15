<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_documentos_emitidos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('solicitud_id');
            $table->string('nombre_original', 255);
            $table->string('storage_key', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('tamano_bytes');
            $table->string('hash_archivo', 128);
            $table->string('actor_accesos_id', 100)->nullable();
            $table->string('actor_nombre_snapshot', 200)->nullable();
            $table->timestamp('fecha_emision');
            $table->timestamps();

            $table->index('solicitud_id');
            $table->foreign('solicitud_id')->references('id')->on('solicitudes')->restrictOnDelete();
        });

        Schema::create('solicitud_aprobaciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('solicitud_id')->unique();
            $table->uuid('solicitud_documento_emitido_id')->nullable();
            $table->text('instrucciones')->nullable();
            $table->string('actor_accesos_id', 100)->nullable();
            $table->string('actor_nombre_snapshot', 200)->nullable();
            $table->string('actor_departamento_snapshot', 200)->nullable();
            $table->timestamp('fecha_aprobacion');
            $table->timestamps();

            $table->foreign('solicitud_id')->references('id')->on('solicitudes')->restrictOnDelete();
            $table->foreign('solicitud_documento_emitido_id')->references('id')->on('solicitud_documentos_emitidos')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_aprobaciones');
        Schema::dropIfExists('solicitud_documentos_emitidos');
    }
};
