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
        Schema::table('auditorias', function (Blueprint $table) {
            $table->dropColumn('actor_departamento_snapshot');
            $table->uuid('entidad_id')->nullable()->change();
        });
    }
};
