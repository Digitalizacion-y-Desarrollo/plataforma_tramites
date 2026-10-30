<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * En la captura administrativa "Datos extraídos del acta de nacimiento"
 * (clave datos_acta_nacimiento) no debe existir el campo "Domicilio de
 * residencia": se captura en el acta solo la información ejecutiva y el
 * domicilio residence se incorpora en los datos del solicitante.
 *
 * Se desactiva el campo en lugar de eliminarlo para preservar la integridad
 * de las respuestas históricas.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('formulario_captura_administrativa_campos')
            ->whereIn('captura_administrativa_id', $this->capturasIdQuery())
            ->where('clave', 'domicilio_residencia')
            ->update(['activo' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        $capturaIds = DB::table('formulario_capturas_administrativas')
            ->where('clave', 'datos_acta_nacimiento')
            ->pluck('id');

        $existentes = DB::table('formulario_captura_administrativa_campos')
            ->whereIn('captura_administrativa_id', $capturaIds)
            ->where('clave', 'domicilio_residencia')
            ->pluck('id', 'captura_administrativa_id');

        if ($existentes->isNotEmpty()) {
            DB::table('formulario_captura_administrativa_campos')
                ->whereIn('id', $existentes->values())
                ->update(['activo' => true, 'updated_at' => now()]);
        }

        $capturaIds->diff($existentes->keys())->each(fn (string $capturaId) => DB::table('formulario_captura_administrativa_campos')->insert([
            'id' => (string) Str::uuid(),
            'captura_administrativa_id' => $capturaId,
            'clave' => 'domicilio_residencia',
            'etiqueta' => 'Domicilio de residencia',
            'tipo' => 'DOMICILIO',
            'requerido' => true,
            'orden' => 18,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    private function capturasIdQuery()
    {
        return DB::table('formulario_capturas_administrativas')
            ->where('clave', 'datos_acta_nacimiento')
            ->select('id');
    }
};
