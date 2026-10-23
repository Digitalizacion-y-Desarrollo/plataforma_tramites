<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitud_pagos', function (Blueprint $table): void {
            if (! Schema::hasColumn('solicitud_pagos', 'comprobante_nombre_original')) {
                $table->string('comprobante_nombre_original', 255)->nullable()->after('pdf_hash_archivo');
            }
            if (! Schema::hasColumn('solicitud_pagos', 'comprobante_storage_key')) {
                $table->string('comprobante_storage_key', 500)->nullable()->after('comprobante_nombre_original');
            }
            if (! Schema::hasColumn('solicitud_pagos', 'comprobante_mime_type')) {
                $table->string('comprobante_mime_type', 120)->nullable()->after('comprobante_storage_key');
            }
            if (! Schema::hasColumn('solicitud_pagos', 'comprobante_tamano_bytes')) {
                $table->unsignedBigInteger('comprobante_tamano_bytes')->nullable()->after('comprobante_mime_type');
            }
            if (! Schema::hasColumn('solicitud_pagos', 'comprobante_hash_archivo')) {
                $table->string('comprobante_hash_archivo', 128)->nullable()->after('comprobante_tamano_bytes');
            }
            if (! Schema::hasColumn('solicitud_pagos', 'fecha_comprobante')) {
                $table->timestamp('fecha_comprobante')->nullable()->after('comprobante_hash_archivo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('solicitud_pagos', function (Blueprint $table): void {
            $columnas = array_values(array_filter(
                ['comprobante_nombre_original', 'comprobante_storage_key', 'comprobante_mime_type', 'comprobante_tamano_bytes', 'comprobante_hash_archivo', 'fecha_comprobante'],
                fn (string $columna): bool => Schema::hasColumn('solicitud_pagos', $columna)
            ));

            if ($columnas !== []) {
                $table->dropColumn($columnas);
            }
        });
    }
};
