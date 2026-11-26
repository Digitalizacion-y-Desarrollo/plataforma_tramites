<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tramites', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_tramite_tesoreria')->nullable()->after('clave');
        });
    }

    public function down(): void
    {
        Schema::table('tramites', function (Blueprint $table): void {
            $table->dropColumn('id_tramite_tesoreria');
        });
    }
};
