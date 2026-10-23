<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitud_observaciones', function (Blueprint $table): void {
            $table->string('actor_tipo', 30)->nullable()->after('visible_ciudadano');
            $table->uuid('respuesta_a_id')->nullable()->after('actor_nombre_snapshot');
            $table->string('actor_accesos_id', 100)->nullable()->change();
            $table->unique('respuesta_a_id', 'solicitud_observaciones_respuesta_a_unica');
            $table->index(['solicitud_id', 'visible_ciudadano', 'created_at'], 'solicitud_observaciones_conversacion_indice');
            $table->foreign('respuesta_a_id')->references('id')->on('solicitud_observaciones')->restrictOnDelete();
        });

        DB::table('solicitud_observaciones')->whereNull('actor_tipo')->update(['actor_tipo' => 'SERVIDOR_PUBLICO']);
    }

    public function down(): void
    {
        Schema::table('solicitud_observaciones', function (Blueprint $table): void {
            $table->dropForeign(['respuesta_a_id']);
            $table->dropUnique('solicitud_observaciones_respuesta_a_unica');
            $table->dropIndex('solicitud_observaciones_conversacion_indice');
            $table->dropColumn(['actor_tipo', 'respuesta_a_id']);
            $table->string('actor_accesos_id', 100)->nullable(false)->change();
        });
    }
};
