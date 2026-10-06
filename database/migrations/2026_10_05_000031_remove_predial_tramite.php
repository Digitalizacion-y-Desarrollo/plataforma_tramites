<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tramites')
            ->where('clave', 'PREDIAL')
            ->update([
                'activo' => false,
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('tramites')
            ->where('clave', 'PREDIAL')
            ->update([
                'activo' => true,
                'deleted_at' => null,
                'updated_at' => now(),
            ]);
    }
};
