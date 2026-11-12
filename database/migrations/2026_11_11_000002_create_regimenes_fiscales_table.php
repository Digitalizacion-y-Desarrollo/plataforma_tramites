<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regimenes_fiscales', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('clave', 10)->unique();
            $table->string('nombre', 120);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::table('regimenes_fiscales', function (Blueprint $table): void {
            $table->index(['activo', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regimenes_fiscales');
    }
};
