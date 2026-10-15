<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tramites', function (Blueprint $table) {
            $table->unsignedInteger('descarga_expira_dias')->nullable()->after('preguntas_frecuentes');
        });

        Schema::table('solicitud_documentos_emitidos', function (Blueprint $table) {
            $table->timestamp('fecha_expiracion')->nullable()->after('fecha_emision');
        });
    }

    public function down(): void
    {
        Schema::table('solicitud_documentos_emitidos', function (Blueprint $table) {
            $table->dropColumn('fecha_expiracion');
        });

        Schema::table('tramites', function (Blueprint $table) {
            $table->dropColumn('descarga_expira_dias');
        });
    }
};
