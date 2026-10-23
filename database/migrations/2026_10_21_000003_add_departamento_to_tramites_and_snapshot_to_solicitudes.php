<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tramites', function (Blueprint $table): void {
            $table->string('departamento_accesos_id', 100)->nullable()->after('descripcion');
            $table->string('departamento_nombre_snapshot', 200)->nullable()->after('departamento_accesos_id');
            $table->index('departamento_accesos_id');
        });

        Schema::table('solicitudes', function (Blueprint $table): void {
            $table->string('departamento_accesos_id_snapshot', 100)->nullable()->after('tramite_id');
            $table->string('departamento_nombre_snapshot', 200)->nullable()->after('departamento_accesos_id_snapshot');
            $table->index('departamento_accesos_id_snapshot');
        });

        Schema::table('tramite_formulario', function (Blueprint $table): void {
            $table->dropUnique('tramite_formulario_tramite_id_unique');
        });

        DB::table('tramite_formulario')
            ->join('formulario_versiones', 'formulario_versiones.id', '=', 'tramite_formulario.formulario_version_id')
            ->join('formularios', 'formularios.id', '=', 'formulario_versiones.formulario_id')
            ->where('tramite_formulario.activo', true)
            ->select('tramite_formulario.tramite_id', 'formularios.departamento_accesos_id', 'formularios.departamento_nombre_snapshot')
            ->orderBy('tramite_formulario.created_at')
            ->get()
            ->each(function (object $vinculo): void {
                DB::table('tramites')->where('id', $vinculo->tramite_id)->update([
                    'departamento_accesos_id' => $vinculo->departamento_accesos_id,
                    'departamento_nombre_snapshot' => $vinculo->departamento_nombre_snapshot,
                ]);
            });

        DB::table('solicitudes')
            ->join('formulario_versiones', 'formulario_versiones.id', '=', 'solicitudes.formulario_version_id')
            ->join('formularios', 'formularios.id', '=', 'formulario_versiones.formulario_id')
            ->update([
                'solicitudes.departamento_accesos_id_snapshot' => DB::raw('formularios.departamento_accesos_id'),
                'solicitudes.departamento_nombre_snapshot' => DB::raw('formularios.departamento_nombre_snapshot'),
            ]);
    }

    public function down(): void
    {
        // El índice único de tramite_id solo se restaura si un trámite no tiene
        // más de un vínculo. Si existen varios formularios por trámite (estado
        // válido desde esta migración) recrearlo fallaría y obligaría a borrar
        // información histórica, por lo que se omite.
        $hayVinculosMultiples = DB::table('tramite_formulario')
            ->select('tramite_id')
            ->groupBy('tramite_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if (! $hayVinculosMultiples) {
            Schema::table('tramite_formulario', function (Blueprint $table): void {
                $table->unique('tramite_id', 'tramite_formulario_tramite_id_unique');
            });
        }

        Schema::table('solicitudes', function (Blueprint $table): void {
            $table->dropIndex(['departamento_accesos_id_snapshot']);
            $table->dropColumn(['departamento_accesos_id_snapshot', 'departamento_nombre_snapshot']);
        });

        Schema::table('tramites', function (Blueprint $table): void {
            $table->dropIndex(['departamento_accesos_id']);
            $table->dropColumn(['departamento_accesos_id', 'departamento_nombre_snapshot']);
        });
    }
};
