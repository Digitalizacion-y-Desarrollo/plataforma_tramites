<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tramites', function (Blueprint $table): void {
            if (! Schema::hasColumn('tramites', 'flujo_pago')) {
                $table->string('flujo_pago', 30)->default('SIN_PAGO')->after('costo');
            }
            if (! Schema::hasColumn('tramites', 'costo_pago')) {
                $table->decimal('costo_pago', 12, 2)->nullable()->after('flujo_pago');
            }
            if (! Schema::hasColumn('tramites', 'pago_vigencia_dias')) {
                $table->unsignedSmallInteger('pago_vigencia_dias')->nullable()->after('costo_pago');
            }
        });

        Schema::table('solicitudes', function (Blueprint $table): void {
            if (! Schema::hasColumn('solicitudes', 'flujo_pago')) {
                $table->string('flujo_pago', 30)->default('SIN_PAGO')->after('email_contacto');
            }
            if (! Schema::hasColumn('solicitudes', 'importe_pago')) {
                $table->decimal('importe_pago', 12, 2)->nullable()->after('flujo_pago');
            }
            if (! Schema::hasColumn('solicitudes', 'pago_vigencia_dias')) {
                $table->unsignedSmallInteger('pago_vigencia_dias')->nullable()->after('importe_pago');
            }
        });

        Schema::create('solicitud_pagos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('solicitud_id')->unique();
            $table->string('referencia', 80)->unique();
            $table->decimal('importe', 12, 2);
            $table->timestamp('fecha_emision');
            $table->timestamp('fecha_vencimiento');
            $table->string('pdf_nombre_original', 255);
            $table->string('pdf_storage_key', 500);
            $table->string('pdf_hash_archivo', 128);
            $table->string('emisor_accesos_id', 100)->nullable();
            $table->string('emisor_nombre_snapshot', 200)->nullable();
            $table->timestamp('fecha_pago_validado')->nullable();
            $table->text('comentario_validacion')->nullable();
            $table->string('validador_accesos_id', 100)->nullable();
            $table->string('validador_nombre_snapshot', 200)->nullable();
            $table->string('validador_departamento_snapshot', 200)->nullable();
            $table->timestamps();
            $table->foreign('solicitud_id')->references('id')->on('solicitudes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_pagos');
        Schema::table('solicitudes', function (Blueprint $table): void {
            $table->dropColumn(['flujo_pago', 'importe_pago', 'pago_vigencia_dias']);
        });
        Schema::table('tramites', function (Blueprint $table): void {
            $table->dropColumn(['flujo_pago', 'costo_pago', 'pago_vigencia_dias']);
        });
    }
};
