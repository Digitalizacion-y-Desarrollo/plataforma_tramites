<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas_operativas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('accesos_departamento_id', 100)->unique();
            $table->string('nombre_snapshot', 200);
            $table->string('siglas_snapshot', 50)->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
            $table->index('activa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas_operativas');
    }
};
