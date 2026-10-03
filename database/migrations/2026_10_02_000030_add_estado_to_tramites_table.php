<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tramites', 'tramite_estado_id')) {
            DB::statement('ALTER TABLE tramite_estados ENGINE=InnoDB');

            Schema::table('tramites', function (Blueprint $table) {
                $table->foreign('tramite_estado_id')->references('id')->on('tramite_estados')->restrictOnDelete();
                $table->index(['activo', 'tramite_estado_id']);
            });

            return;
        }

        Schema::table('tramites', function (Blueprint $table) {
            $table->foreignId('tramite_estado_id')->nullable()->after('activo')->constrained('tramite_estados')->restrictOnDelete();
            $table->index(['activo', 'tramite_estado_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tramites', function (Blueprint $table) {
            $table->dropForeign(['tramite_estado_id']);
            $table->dropIndex(['activo', 'tramite_estado_id']);
            $table->dropColumn('tramite_estado_id');
        });
    }
};
