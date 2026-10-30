<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formulario_capturas_administrativas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('formulario_version_id');
            $table->uuid('campo_fuente_id')->nullable();
            $table->string('clave', 100);
            $table->string('titulo', 200);
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['formulario_version_id', 'clave'], 'form_captura_version_clave_unique');
            $table->index(['formulario_version_id', 'orden'], 'form_captura_version_orden_index');
            $table->foreign('formulario_version_id', 'form_captura_version_fk')->references('id')->on('formulario_versiones')->restrictOnDelete();
            $table->foreign('campo_fuente_id', 'form_captura_fuente_fk')->references('id')->on('formulario_campos')->restrictOnDelete();
        });

        Schema::create('formulario_captura_administrativa_campos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('captura_administrativa_id');
            $table->string('clave', 100);
            $table->string('etiqueta', 200);
            $table->string('tipo', 50)->default('TEXT');
            $table->boolean('requerido')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->json('configuracion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['captura_administrativa_id', 'clave'], 'form_captura_campo_clave_unique');
            $table->index(['captura_administrativa_id', 'orden'], 'form_captura_campo_orden_index');
            $table->foreign('captura_administrativa_id', 'captura_administrativa_campos_fk')
                ->references('id')->on('formulario_capturas_administrativas')->restrictOnDelete();
        });

        Schema::create('solicitud_captura_administrativa_respuestas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('solicitud_id');
            $table->uuid('campo_captura_administrativa_id');
            $table->json('valor')->nullable();
            $table->string('actor_accesos_id', 100)->nullable();
            $table->string('actor_nombre_snapshot', 200)->nullable();
            $table->string('actor_departamento_snapshot', 200)->nullable();
            $table->timestamps();
            $table->unique(['solicitud_id', 'campo_captura_administrativa_id'], 'solicitud_captura_admin_respuesta_unique');
            $table->foreign('solicitud_id')->references('id')->on('solicitudes')->restrictOnDelete();
            $table->foreign('campo_captura_administrativa_id', 'solicitud_captura_admin_campo_fk')
                ->references('id')->on('formulario_captura_administrativa_campos')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_captura_administrativa_respuestas');
        Schema::dropIfExists('formulario_captura_administrativa_campos');
        Schema::dropIfExists('formulario_capturas_administrativas');
    }
};
