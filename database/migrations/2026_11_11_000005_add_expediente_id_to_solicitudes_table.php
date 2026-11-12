<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes', function (Blueprint $table): void {
            $table->foreignUuid('expediente_id')->nullable()->after('id')->constrained('expedientes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table): void {
            $table->dropForeign(['expediente_id']);
            $table->dropColumn('expediente_id');
        });
    }
};
