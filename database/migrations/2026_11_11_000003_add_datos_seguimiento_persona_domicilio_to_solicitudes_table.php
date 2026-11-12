<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes', function (Blueprint $table): void {
            $table->string('tipo_persona', 10)->nullable()->after('apellido_materno_solicitante');
            $table->string('razon_social', 200)->nullable()->after('tipo_persona');
            $table->string('calle', 200)->nullable()->after('razon_social');
            $table->string('numero_exterior', 30)->nullable()->after('calle');
            $table->string('colonia', 200)->nullable()->after('numero_exterior');
            $table->string('codigo_postal', 10)->nullable()->after('colonia');
            $table->foreignUuid('municipio_id')->nullable()->after('codigo_postal')->constrained('municipios')->nullOnDelete();
            $table->foreignUuid('regimen_fiscal_id')->nullable()->after('municipio_id')->constrained('regimenes_fiscales')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table): void {
            $table->dropForeign(['municipio_id']);
            $table->dropForeign(['regimen_fiscal_id']);
            $table->dropColumn(['tipo_persona', 'razon_social', 'calle', 'numero_exterior', 'colonia', 'codigo_postal', 'municipio_id', 'regimen_fiscal_id']);
        });
    }
};
