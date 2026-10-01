<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tramite_formulario', function (Blueprint $table) {
            $table->unique('tramite_id', 'tramite_formulario_tramite_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tramite_formulario', function (Blueprint $table) {
            $table->dropUnique('tramite_formulario_tramite_id_unique');
        });
    }
};
