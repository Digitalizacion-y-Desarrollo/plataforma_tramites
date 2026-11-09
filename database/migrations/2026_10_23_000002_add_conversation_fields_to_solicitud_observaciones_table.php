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
        });

        $this->garantizarIndiceSolicitudId();

        Schema::table('solicitud_observaciones', function (Blueprint $table): void {
            $table->dropIndex('solicitud_observaciones_conversacion_indice');
            $table->dropColumn(['actor_tipo', 'respuesta_a_id']);
            $table->string('actor_accesos_id', 100)->nullable(false)->change();
        });
    }

    /**
     * MySQL no permite eliminar un índice si es el único que respalda una
     * llave foránea (error 1553). Antes de eliminar el índice compuesto
     * garantizamos que exista otro índice que empiece por solicitud_id.
     */
    private function garantizarIndiceSolicitudId(): void
    {
        $tieneIndice = collect(Schema::getIndexes('solicitud_observaciones'))
            ->contains(fn (array $indice): bool => $indice['name'] !== 'solicitud_observaciones_conversacion_indice'
                && ($indice['columns'][0] ?? null) === 'solicitud_id');

        if ($tieneIndice) {
            return;
        }

        Schema::table('solicitud_observaciones', function (Blueprint $table): void {
            $table->index('solicitud_id', 'solicitud_observaciones_solicitud_id_indice');
        });
    }
};
