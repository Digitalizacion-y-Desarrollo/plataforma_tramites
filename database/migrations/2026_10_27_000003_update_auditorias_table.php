<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auditorias', function (Blueprint $table) {
            $table->string('entidad_id', 36)->nullable()->change();
            $table->string('actor_departamento_snapshot', 200)->nullable()->after('actor_nombre_snapshot');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('auditorias', 'actor_departamento_snapshot')) {
            Schema::table('auditorias', function (Blueprint $table) {
                $table->dropColumn('actor_departamento_snapshot');
            });
        }

        // No se revierte entidad_id a uuid (char(36)): la up() lo amplió a
        // string(36) para admitir identificadores que no son UUID. Revertir
        // truncaría información histórica de auditoría.
    }
};
