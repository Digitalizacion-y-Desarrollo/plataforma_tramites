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
        Schema::table('formularios', function (Blueprint $table) {
            $table->dropIndex(['area_accesos_id']);
            $table->dropColumn(['area_accesos_id', 'area_nombre_snapshot']);
        });
    }
};
