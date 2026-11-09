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
        $tieneForeignKey = collect(Schema::getForeignKeys('formulario_grupo_campo'))
            ->contains(fn (array $fk): bool => $fk['name'] === 'formulario_grupo_campo_campo_id_foreign');
        $tieneUnique = Schema::hasIndex('formulario_grupo_campo', 'formulario_grupo_campo_campo_id_unique');
        $tieneIndice = Schema::hasIndex('formulario_grupo_campo', 'formulario_grupo_campo_campo_id_index');

        if ($tieneForeignKey) {
            Schema::table('formulario_grupo_campo', function (Blueprint $table) {
                $table->dropForeign(['campo_id']);
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

        if ($tieneForeignKey) {
            Schema::table('formulario_grupo_campo', function (Blueprint $table) {
                $table->foreign('campo_id')
                    ->references('id')
                    ->on('formulario_campos')
                    ->restrictOnDelete();
            });
        }
    }
};
