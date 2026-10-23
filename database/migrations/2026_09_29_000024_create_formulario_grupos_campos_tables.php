<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('formulario_grupos_campos')) {
            Schema::create('formulario_grupos_campos', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->uuid('id')->primary();
                $table->uuid('formulario_version_id');
                $table->string('titulo', 200);
                $table->unsignedInteger('orden')->default(0);
                $table->boolean('activo')->default(true);
                $table->timestamps();
                $table->index(['formulario_version_id', 'orden']);
            });
        }

        if (! Schema::hasTable('formulario_grupo_campo')) {
            Schema::create('formulario_grupo_campo', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->uuid('id')->primary();
                $table->uuid('grupo_id');
                $table->uuid('campo_id');
                $table->timestamps();
                $table->unique(['grupo_id', 'campo_id']);
            });
        }

        DB::statement('ALTER TABLE formulario_grupos_campos ENGINE = InnoDB');
        DB::statement('ALTER TABLE formulario_grupo_campo ENGINE = InnoDB');

        Schema::table('campo_condiciones', function (Blueprint $table) {
            if (! Schema::hasColumn('campo_condiciones', 'grupo_destino_id')) {
                $table->uuid('grupo_destino_id')->nullable()->after('campo_destino_id');
            }
        });

        DB::statement('ALTER TABLE campo_condiciones MODIFY campo_destino_id CHAR(36) NULL');

        $grupoClaves = collect(Schema::getForeignKeys('formulario_grupos_campos'))->pluck('name');
        $pivotClaves = collect(Schema::getForeignKeys('formulario_grupo_campo'))->pluck('name');
        $condicionClaves = collect(Schema::getForeignKeys('campo_condiciones'))->pluck('name');

        Schema::table('formulario_grupos_campos', function (Blueprint $table) use ($grupoClaves) {
            if (! $grupoClaves->contains('formulario_grupos_campos_formulario_version_id_foreign')) {
                $table->foreign('formulario_version_id')->references('id')->on('formulario_versiones')->restrictOnDelete();
            }
        });
        Schema::table('formulario_grupo_campo', function (Blueprint $table) use ($pivotClaves) {
            if (! $pivotClaves->contains('formulario_grupo_campo_grupo_id_foreign')) {
                $table->foreign('grupo_id')->references('id')->on('formulario_grupos_campos')->cascadeOnDelete();
            }
            if (! $pivotClaves->contains('formulario_grupo_campo_campo_id_foreign')) {
                $table->foreign('campo_id')->references('id')->on('formulario_campos')->restrictOnDelete();
            }
        });
        Schema::table('campo_condiciones', function (Blueprint $table) use ($condicionClaves) {
            if (! $condicionClaves->contains('campo_condiciones_grupo_destino_id_foreign')) {
                $table->foreign('grupo_destino_id')->references('id')->on('formulario_grupos_campos')->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('campo_condiciones', function (Blueprint $table) {
            $table->dropForeign(['grupo_destino_id']);
            $table->dropColumn('grupo_destino_id');
        });

        // campo_destino_id se vuelve obligatorio solo si no existen condiciones
        // dirigidas a un grupo (campo_destino_id NULL). Revertirlo con esos datos
        // provocaría un error y obligaría a borrar configuración válida.
        $hayCondicionesDeGrupo = DB::table('campo_condiciones')
            ->whereNull('campo_destino_id')
            ->exists();

        if (! $hayCondicionesDeGrupo) {
            Schema::table('campo_condiciones', function (Blueprint $table) {
                $table->uuid('campo_destino_id')->nullable(false)->change();
            });
        }

        Schema::dropIfExists('formulario_grupo_campo');
        Schema::dropIfExists('formulario_grupos_campos');
    }
};
