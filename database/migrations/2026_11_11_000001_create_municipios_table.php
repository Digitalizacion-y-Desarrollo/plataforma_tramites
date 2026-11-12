<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipios', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('nombre', 120);
            $table->string('estado', 120)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::table('municipios', function (Blueprint $table): void {
            $table->unique('nombre');
            $table->index(['activo', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipios');
    }
};
