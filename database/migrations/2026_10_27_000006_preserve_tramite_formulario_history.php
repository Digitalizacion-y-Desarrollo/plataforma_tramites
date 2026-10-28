<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tramite_formulario')) {
            return;
        }

        Schema::table('tramite_formulario', function (Blueprint $table): void {
            if (Schema::hasIndex('tramite_formulario', 'tramite_formulario_tramite_id_unique')) {
                $table->dropUnique('tramite_formulario_tramite_id_unique');
            }

            if (! Schema::hasIndex('tramite_formulario', 'tramite_formulario_tramite_version_unique')) {
                $table->unique(['tramite_id', 'formulario_version_id'], 'tramite_formulario_tramite_version_unique');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tramite_formulario')) {
            return;
        }

        Schema::table('tramite_formulario', function (Blueprint $table): void {
            if (Schema::hasIndex('tramite_formulario', 'tramite_formulario_tramite_version_unique')) {
                $table->dropUnique('tramite_formulario_tramite_version_unique');
            }

            // No se restaura la unicidad individual de tramite_id: una publicación
            // posterior puede haber creado vínculos históricos que no deben borrarse.
        });
    }
};
