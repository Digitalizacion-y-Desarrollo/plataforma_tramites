<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formularios', function (Blueprint $table) {
            $table->dropIndex(['area_accesos_id']);
            $table->renameColumn('area_accesos_id', 'departamento_accesos_id');
            $table->renameColumn('area_nombre_snapshot', 'departamento_nombre_snapshot');
            $table->string('subdepartamento_accesos_id', 100)->nullable()->after('departamento_nombre_snapshot');
            $table->string('subdepartamento_nombre_snapshot', 200)->nullable()->after('subdepartamento_accesos_id');
            $table->index('departamento_accesos_id');
            $table->index('subdepartamento_accesos_id');
        });
    }

    public function down(): void
    {
        Schema::table('formularios', function (Blueprint $table) {
            $table->dropIndex(['departamento_accesos_id']);
            $table->dropIndex(['subdepartamento_accesos_id']);
            $table->dropColumn(['subdepartamento_accesos_id', 'subdepartamento_nombre_snapshot']);
            $table->renameColumn('departamento_accesos_id', 'area_accesos_id');
            $table->renameColumn('departamento_nombre_snapshot', 'area_nombre_snapshot');
            $table->index('area_accesos_id');
        });
    }
};
