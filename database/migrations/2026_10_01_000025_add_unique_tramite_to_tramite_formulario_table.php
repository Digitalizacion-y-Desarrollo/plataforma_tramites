<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A partir de 2026_10_21_000003 un trámite puede tener varios
        // formularios vinculados. En ese caso no se puede imponer la
        // unicidad individual de tramite_id.
        $hayVinculosMultiples = DB::table('tramite_formulario')
            ->select('tramite_id')
            ->groupBy('tramite_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hayVinculosMultiples || Schema::hasIndex('tramite_formulario', 'tramite_formulario_tramite_id_unique')) {
            return;
        }

        Schema::table('tramite_formulario', function (Blueprint $table) {
            $table->unique('tramite_id', 'tramite_formulario_tramite_id_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasIndex('tramite_formulario', 'tramite_formulario_tramite_id_unique')) {
            return;
        }

        Schema::table('tramite_formulario', function (Blueprint $table) {
            $table->dropUnique('tramite_formulario_tramite_id_unique');
        });
    }
};
