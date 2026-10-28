<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('solicitudes', 'aviso_privacidad_aceptado_at')) {
            return;
        }

        Schema::table('solicitudes', function (Blueprint $table): void {
            $table->timestamp('aviso_privacidad_aceptado_at')
                ->nullable()
                ->after('fecha_terminacion');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('solicitudes', 'aviso_privacidad_aceptado_at')) {
            return;
        }

        Schema::table('solicitudes', function (Blueprint $table): void {
            $table->dropColumn('aviso_privacidad_aceptado_at');
        });
    }
};
