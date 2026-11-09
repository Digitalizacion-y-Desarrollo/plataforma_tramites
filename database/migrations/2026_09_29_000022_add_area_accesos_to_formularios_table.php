<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formularios', function (Blueprint $table) {
            $table->string('area_accesos_id', 100)->nullable()->after('descripcion');
            $table->string('area_nombre_snapshot', 200)->nullable()->after('area_accesos_id');
            $table->index('area_accesos_id');
        });
    }

    public function down(): void
    {
        $tieneIndice = Schema::hasIndex('formularios', 'formularios_area_accesos_id_index');
        $columnas = array_values(array_filter(
            ['area_accesos_id', 'area_nombre_snapshot'],
            fn (string $columna): bool => Schema::hasColumn('formularios', $columna)
        ));

        Schema::table('formularios', function (Blueprint $table) use ($tieneIndice, $columnas) {
            if ($tieneIndice) {
                $table->dropIndex(['area_accesos_id']);
            }
            if ($columnas !== []) {
                $table->dropColumn($columnas);
            }
        });
    }
};
