<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tramites', function (Blueprint $table): void {
            if (! Schema::hasColumn('tramites', 'doc_a_obtener')) {
                $table->text('doc_a_obtener')->nullable();
            }
            if (! Schema::hasColumn('tramites', 'vigencia_doc')) {
                $table->text('vigencia_doc')->nullable();
            }
            if (! Schema::hasColumn('tramites', 'sujeto_inspeccion')) {
                $table->text('sujeto_inspeccion')->nullable();
            }
            if (! Schema::hasColumn('tramites', 'criterios_resolucion')) {
                $table->longText('criterios_resolucion')->nullable();
            }
            if (! Schema::hasColumn('tramites', 'unidad_administrativa_responsable')) {
                $table->text('unidad_administrativa_responsable')->nullable();
            }
        });
    }

    public function down(): void
    {
        $columnas = array_values(array_filter([
            'doc_a_obtener',
            'vigencia_doc',
            'sujeto_inspeccion',
            'criterios_resolucion',
            'unidad_administrativa_responsable',
        ], fn (string $columna): bool => Schema::hasColumn('tramites', $columna)));

        if ($columnas !== []) {
            Schema::table('tramites', function (Blueprint $table) use ($columnas): void {
                $table->dropColumn($columnas);
            });
        }
    }
};
