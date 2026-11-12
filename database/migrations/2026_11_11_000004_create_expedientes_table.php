<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expedientes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('curp_consulta', 18);
            $table->string('tipo_persona', 10);
            $table->string('id_externo', 100)->nullable()->unique();
            $table->string('email_contacto', 200);
            $table->string('telefono_contacto', 30)->nullable();
            $table->string('nombres_solicitante', 100)->nullable();
            $table->string('apellido_paterno_solicitante', 100)->nullable();
            $table->string('apellido_materno_solicitante', 100)->nullable();
            $table->string('razon_social', 200)->nullable();
            $table->string('calle', 200);
            $table->string('numero_exterior', 30);
            $table->string('colonia', 200);
            $table->string('codigo_postal', 10);
            $table->foreignUuid('municipio_id')->constrained('municipios')->restrictOnDelete();
            $table->foreignUuid('regimen_fiscal_id')->constrained('regimenes_fiscales')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['curp_consulta', 'tipo_persona']);
            $table->index('email_contacto');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expedientes');
    }
};
