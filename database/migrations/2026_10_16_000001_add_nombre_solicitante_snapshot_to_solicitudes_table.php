<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->string('nombre_solicitante_snapshot', 200)->nullable()->after('email_contacto');
            $table->index(['curp_consulta', 'email_contacto', 'fecha_envio'], 'solicitudes_consulta_ciudadana_index');
        });
    }

    public function down(): void
    {
        $tieneIndice = Schema::hasIndex('solicitudes', 'solicitudes_consulta_ciudadana_index');
        $tieneColumna = Schema::hasColumn('solicitudes', 'nombre_solicitante_snapshot');

        Schema::table('solicitudes', function (Blueprint $table) use ($tieneIndice, $tieneColumna) {
            if ($tieneIndice) {
                $table->dropIndex('solicitudes_consulta_ciudadana_index');
            }
            if ($tieneColumna) {
                $table->dropColumn('nombre_solicitante_snapshot');
            }
        });
    }
};
