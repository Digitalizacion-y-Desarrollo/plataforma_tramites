<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes', function (Blueprint $table): void {
            $table->dropForeign(['municipio_id']);
        });
        Schema::table('expedientes', function (Blueprint $table): void {
            $table->dropForeign(['municipio_id']);
        });

        DB::table('solicitudes')->update(['municipio_id' => '715']);
        DB::table('expedientes')->update(['municipio_id' => '715']);

        Schema::table('solicitudes', function (Blueprint $table): void {
            $table->unsignedBigInteger('municipio_id')->nullable()->change();
        });
        Schema::table('expedientes', function (Blueprint $table): void {
            $table->unsignedBigInteger('municipio_id')->change();
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table): void {
            $table->uuid('municipio_id')->nullable()->change();
        });
        Schema::table('expedientes', function (Blueprint $table): void {
            $table->uuid('municipio_id')->nullable()->change();
        });

        $municipioId = DB::table('municipios')->where('nombre', 'Nezahualcóyotl')->value('id');
        DB::table('solicitudes')->update(['municipio_id' => $municipioId]);
        DB::table('expedientes')->update(['municipio_id' => $municipioId]);

        Schema::table('solicitudes', function (Blueprint $table): void {
            $table->foreign('municipio_id')->references('id')->on('municipios')->nullOnDelete();
        });
        Schema::table('expedientes', function (Blueprint $table): void {
            $table->foreign('municipio_id')->references('id')->on('municipios')->nullOnDelete();
        });
    }
};
