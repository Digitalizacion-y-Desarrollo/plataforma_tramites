<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes', function (Blueprint $table): void {
            $table->string('nombres_solicitante', 100)->nullable()->after('nombre_solicitante_snapshot');
            $table->string('apellido_paterno_solicitante', 100)->nullable()->after('nombres_solicitante');
            $table->string('apellido_materno_solicitante', 100)->nullable()->after('apellido_paterno_solicitante');
            $table->string('telefono_contacto', 30)->nullable()->after('email_contacto');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table): void {
            $table->dropColumn(['nombres_solicitante', 'apellido_paterno_solicitante', 'apellido_materno_solicitante', 'telefono_contacto']);
        });
    }
};
