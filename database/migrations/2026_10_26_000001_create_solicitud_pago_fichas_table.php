<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitud_pagos', function (Blueprint $table): void {
            $table->string('referencia', 80)->nullable()->change();
            $table->string('pdf_nombre_original', 255)->nullable()->change();
            $table->string('pdf_storage_key', 500)->nullable()->change();
            $table->string('pdf_hash_archivo', 128)->nullable()->change();
        });

        Schema::create('solicitud_pago_fichas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('solicitud_pago_id');
            $table->unsignedSmallInteger('version');
            $table->string('nombre_original', 255);
            $table->string('storage_key', 500);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('tamano_bytes');
            $table->string('hash_archivo', 128);
            $table->text('indicaciones')->nullable();
            $table->text('motivo_reemplazo')->nullable();
            $table->boolean('es_vigente')->default(true);
            $table->string('actor_accesos_id', 100)->nullable();
            $table->string('actor_nombre_snapshot', 200)->nullable();
            $table->string('actor_departamento_snapshot', 200)->nullable();
            $table->timestamps();

            $table->unique(['solicitud_pago_id', 'version']);
            $table->index(['solicitud_pago_id', 'es_vigente']);
            $table->foreign('solicitud_pago_id')->references('id')->on('solicitud_pagos')->restrictOnDelete();
        });

        DB::table('solicitud_pagos')
            ->whereNotNull('pdf_storage_key')
            ->orderBy('id')
            ->each(function (object $pago): void {
                DB::table('solicitud_pago_fichas')->insert([
                    'id' => (string) Str::uuid(),
                    'solicitud_pago_id' => $pago->id,
                    'version' => 1,
                    'nombre_original' => $pago->pdf_nombre_original,
                    'storage_key' => $pago->pdf_storage_key,
                    'mime_type' => 'application/pdf',
                    'tamano_bytes' => 0,
                    'hash_archivo' => $pago->pdf_hash_archivo,
                    'indicaciones' => null,
                    'motivo_reemplazo' => null,
                    'es_vigente' => true,
                    'created_at' => $pago->created_at,
                    'updated_at' => $pago->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_pago_fichas');

        Schema::table('solicitud_pagos', function (Blueprint $table): void {
            $table->string('referencia', 80)->nullable(false)->change();
            $table->string('pdf_nombre_original', 255)->nullable(false)->change();
            $table->string('pdf_storage_key', 500)->nullable(false)->change();
            $table->string('pdf_hash_archivo', 128)->nullable(false)->change();
        });
    }
};
