<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tramite_estados') || ! Schema::hasTable('tramites')) {
            return;
        }

        $publicadoId = DB::table('tramite_estados')->where('clave', 'PUBLICADO')->value('id');
        $borradorId = DB::table('tramite_estados')->where('clave', 'BORRADOR')->value('id');

        if ($publicadoId) {
            DB::table('tramites')
                ->whereNull('tramite_estado_id')
                ->where('activo', true)
                ->update(['tramite_estado_id' => $publicadoId, 'updated_at' => now()]);
        }

        if ($borradorId) {
            DB::table('tramites')
                ->whereNull('tramite_estado_id')
                ->where('activo', false)
                ->update(['tramite_estado_id' => $borradorId, 'updated_at' => now()]);
        }

        if (! $publicadoId || ! $borradorId || ! Schema::hasTable('formulario_estados') || ! Schema::hasTable('formulario_versiones')) {
            return;
        }

        $formularioPublicadoId = DB::table('formulario_estados')->where('clave', 'PUBLICADO')->value('id');
        $formularioBorradorId = DB::table('formulario_estados')->where('clave', 'BORRADOR')->value('id');

        if (! $formularioPublicadoId || ! $formularioBorradorId || ! Schema::hasTable('tramite_formulario')) {
            return;
        }

        $versionesPublicadas = DB::table('tramite_formulario as vinculo')
            ->join('tramites as tramite', 'tramite.id', '=', 'vinculo.tramite_id')
            ->join('formulario_versiones as version', 'version.id', '=', 'vinculo.formulario_version_id')
            ->where('vinculo.activo', true)
            ->where('tramite.tramite_estado_id', $publicadoId)
            ->where('version.formulario_estado_id', $formularioBorradorId)
            ->distinct()
            ->pluck('version.id');

        foreach ($versionesPublicadas as $versionId) {
            DB::table('formulario_versiones')
                ->where('id', $versionId)
                ->where('formulario_estado_id', $formularioBorradorId)
                ->update([
                    'formulario_estado_id' => $formularioPublicadoId,
                    'fecha_publicacion' => now(),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // La corrección de estados es una migración de datos y no se revierte
        // para no perder la clasificación histórica de trámites y versiones.
    }
};
