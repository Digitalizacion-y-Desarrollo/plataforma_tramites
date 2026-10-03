<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tramites', function (Blueprint $table) {
            $table->string('duracion', 100)->nullable()->after('descripcion');
            $table->string('costo', 100)->nullable()->after('duracion');
            $table->string('tiempo_estimado', 100)->nullable()->after('costo');
            $table->json('modalidades')->nullable()->after('tiempo_estimado');
            $table->json('requisitos')->nullable()->after('modalidades');
            $table->json('proceso')->nullable()->after('requisitos');
            $table->json('preguntas_frecuentes')->nullable()->after('proceso');
        });
    }

    public function down(): void
    {
        Schema::table('tramites', function (Blueprint $table) {
            $table->dropColumn([
                'duracion',
                'costo',
                'tiempo_estimado',
                'modalidades',
                'requisitos',
                'proceso',
                'preguntas_frecuentes',
            ]);
        });
    }
};
