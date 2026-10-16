<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_correccion_documentos', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->uuid('id')->primary();
            $table->uuid('solicitud_correccion_id');
            $table->uuid('campo_id');
            $table->text('motivo');
            $table->json('archivo_original');
            $table->json('archivo_corregido')->nullable();
            $table->timestamp('fecha_carga')->nullable();
            $table->timestamps();

            $table->unique(['solicitud_correccion_id', 'campo_id'], 'sol_corr_doc_correccion_campo_unique');
            $table->index('campo_id', 'sol_corr_doc_campo_index');
            $table->foreign('solicitud_correccion_id')->references('id')->on('solicitud_correcciones')->restrictOnDelete();
            $table->foreign('campo_id')->references('id')->on('formulario_campos')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_correccion_documentos');
    }
};
