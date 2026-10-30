<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El campo "Tiempo de residencia" no debe capturarse en la captura
 * administrativa "Datos extraídos del acta de nacimiento".
 *
 * Se desactiva en lugar de eliminarlo para preservar la integridad de las
 * respuestas históricas.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('formulario_captura_administrativa_campos')
            ->whereIn('captura_administrativa_id', $this->capturasIdQuery())
            ->where('clave', 'tiempo_residencia')
            ->update(['activo' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('formulario_captura_administrativa_campos')
            ->whereIn('captura_administrativa_id', $this->capturasIdQuery())
            ->where('clave', 'tiempo_residencia')
            ->update(['activo' => true, 'updated_at' => now()]);
    }

    private function capturasIdQuery()
    {
        return DB::table('formulario_capturas_administrativas')
            ->where('clave', 'datos_acta_nacimiento')
            ->select('id');
    }
};
