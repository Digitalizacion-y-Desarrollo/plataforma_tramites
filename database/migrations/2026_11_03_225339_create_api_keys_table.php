<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 150);
            $table->string('prefijo', 24)->unique();
            $table->char('token_hash', 64)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamp('expira_en')->nullable()->index();
            $table->timestamp('ultimo_uso_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
