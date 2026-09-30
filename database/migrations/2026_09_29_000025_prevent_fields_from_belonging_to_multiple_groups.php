<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formulario_grupo_campo', function (Blueprint $table) {
            $table->unique('campo_id', 'formulario_grupo_campo_campo_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('formulario_grupo_campo', function (Blueprint $table) {
            $table->dropUnique('formulario_grupo_campo_campo_id_unique');
        });
    }
};
