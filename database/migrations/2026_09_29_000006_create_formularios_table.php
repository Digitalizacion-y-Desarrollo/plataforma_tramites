<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('formularios', function (Blueprint $t) { $t->engine='InnoDB'; $t->charset='utf8mb4'; $t->collation='utf8mb4_unicode_ci'; $t->uuid('id')->primary(); $t->string('nombre',200); $t->text('descripcion')->nullable(); $t->string('creado_por_accesos_id',100)->nullable(); $t->timestamps(); }); } public function down(): void { Schema::dropIfExists('formularios'); } };
