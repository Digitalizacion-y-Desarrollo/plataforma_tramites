<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('formulario_grupo_campo', 'formulario_grupo_campo_campo_id_unique')) {
            return;
        }

        Schema::table('formulario_grupo_campo', function (Blueprint $table) {
            $table->unique('campo_id', 'formulario_grupo_campo_campo_id_unique');
        });
    }

    public function down(): void
    {
        $foreignKeyCampo = collect(Schema::getForeignKeys('formulario_grupo_campo'))
            ->first(fn (array $fk): bool => in_array('campo_id', $fk['columns'], true));
        $tieneUnique = Schema::hasIndex('formulario_grupo_campo', 'formulario_grupo_campo_campo_id_unique');
        $tieneIndice = Schema::hasIndex('formulario_grupo_campo', 'formulario_grupo_campo_campo_id_index');

        if ($foreignKeyCampo !== null) {
            Schema::table('formulario_grupo_campo', function (Blueprint $table) use ($foreignKeyCampo) {
                $table->dropForeign($foreignKeyCampo['name']);
            });
        }

        if ($tieneUnique) {
            Schema::table('formulario_grupo_campo', function (Blueprint $table) {
                $table->dropUnique('formulario_grupo_campo_campo_id_unique');
            });
        }

        if (! $tieneIndice) {
            Schema::table('formulario_grupo_campo', function (Blueprint $table) {
                $table->index('campo_id', 'formulario_grupo_campo_campo_id_index');
            });
        }

        if ($foreignKeyCampo !== null) {
            Schema::table('formulario_grupo_campo', function (Blueprint $table) {
                $table->foreign('campo_id')
                    ->references('id')
                    ->on('formulario_campos')
                    ->restrictOnDelete();
            });
        }
    }
};
