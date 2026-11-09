<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tramites', 'tramite_estado_id')) {
            DB::statement('ALTER TABLE tramite_estados ENGINE=InnoDB');

            $tieneForeignKey = collect(Schema::getForeignKeys('tramites'))
                ->contains(fn (array $fk): bool => in_array('tramite_estado_id', $fk['columns'], true));
            $tieneIndice = Schema::hasIndex('tramites', 'tramites_activo_tramite_estado_id_index');

            Schema::table('tramites', function (Blueprint $table) use ($tieneForeignKey, $tieneIndice) {
                if (! $tieneForeignKey) {
                    $table->foreign('tramite_estado_id')->references('id')->on('tramite_estados')->restrictOnDelete();
                }
                if (! $tieneIndice) {
                    $table->index(['activo', 'tramite_estado_id']);
                }
            });

            return;
        }

        Schema::table('tramites', function (Blueprint $table) {
            $table->foreignId('tramite_estado_id')->nullable()->after('activo')->constrained('tramite_estados')->restrictOnDelete();
            $table->index(['activo', 'tramite_estado_id']);
        });
    }

    public function down(): void
    {
        $foreignKeyEstado = collect(Schema::getForeignKeys('tramites'))
            ->first(fn (array $fk): bool => in_array('tramite_estado_id', $fk['columns'], true));
        $tieneIndice = Schema::hasIndex('tramites', 'tramites_activo_tramite_estado_id_index');
        $tieneColumna = Schema::hasColumn('tramites', 'tramite_estado_id');

        if ($foreignKeyEstado !== null) {
            Schema::table('tramites', function (Blueprint $table) use ($foreignKeyEstado) {
                $table->dropForeign($foreignKeyEstado['name']);
            });
        }

        Schema::table('tramites', function (Blueprint $table) use ($tieneIndice, $tieneColumna) {
            if ($tieneIndice) {
                $table->dropIndex(['activo', 'tramite_estado_id']);
            }
            if ($tieneColumna) {
                $table->dropColumn('tramite_estado_id');
            }
        });
    }
};
