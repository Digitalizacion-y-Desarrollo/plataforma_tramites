<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SolicitudEstadoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['BORRADOR', 'Borrador', 1, false], ['ENVIADA', 'Enviada', 2, false], ['CORRECCION_SOLICITADA', 'Corrección solicitada', 3, false], ['APROBADA', 'Aprobada', 4, true], ['RECHAZADA', 'Rechazada', 5, true]] as [$clave,$nombre,$orden,$final]) {
            $data = ['nombre' => $nombre, 'descripcion' => null, 'orden' => $orden, 'es_final' => $final, 'activo' => true, 'updated_at' => now()];
            if (DB::table('solicitud_estados')->where('clave', $clave)->exists()) {
                DB::table('solicitud_estados')->where('clave', $clave)->update($data);
            } else {
                DB::table('solicitud_estados')->insert($data + ['clave' => $clave, 'created_at' => now()]);
            }
        }
    }
}
