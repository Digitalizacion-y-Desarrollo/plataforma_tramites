<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('token_consultas', function (Blueprint $table): void {
            $table->string('proposito', 30)->default('CONSULTA')->after('email_destino');
            $table->index(['curp', 'email_destino', 'proposito', 'utilizado'], 'token_consultas_proposito_index');
        });
    }

    public function down(): void
    {
        Schema::table('token_consultas', function (Blueprint $table): void {
            $table->dropIndex('token_consultas_proposito_index');
            $table->dropColumn('proposito');
        });
    }
};
