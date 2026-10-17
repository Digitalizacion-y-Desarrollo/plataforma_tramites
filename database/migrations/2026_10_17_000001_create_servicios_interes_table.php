<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicios_interes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('titulo', 160);
            $table->text('descripcion');
            $table->string('enlace', 2048);
            $table->string('logo_storage_key');
            $table->timestamps();

            $table->index('titulo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios_interes');
    }
};
