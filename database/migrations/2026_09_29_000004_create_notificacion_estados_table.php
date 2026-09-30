<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('notificacion_estados', function (Blueprint $t) { $t->engine='InnoDB'; $t->charset='utf8mb4'; $t->collation='utf8mb4_unicode_ci'; $t->id(); $t->string('clave',50)->unique(); $t->string('nombre',100); $t->unsignedInteger('orden')->default(0); $t->boolean('activo')->default(true); $t->timestamps(); }); } public function down(): void { Schema::dropIfExists('notificacion_estados'); } };
