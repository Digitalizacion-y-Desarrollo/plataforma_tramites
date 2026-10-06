<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TramiteSeeder extends Seeder
{
    public function run(): void
    {
        $estadoPublicadoId = DB::table('tramite_estados')->where('clave', 'PUBLICADO')->value('id');

        foreach ([['CONST_REG', 'Constancia de regularización', null], ['TERM_OBRA', 'Constancia de terminación de obra', null], ['REG_CONDOMINIO', 'Constancia de cambio o régimen en condominio', null], ['SUSP_OBRA', 'Constancia de suspensión de obra', null]] as [$clave,$nombre,$detalle]) {
            $data = ['nombre' => $nombre, 'descripcion' => $detalle['descripcion'] ?? null, 'activo' => true, 'tramite_estado_id' => $estadoPublicadoId, 'updated_at' => now()];
            if ($detalle) {
                $data += collect($detalle)->except('descripcion')->all();
            } if (DB::table('tramites')->where('clave', $clave)->exists()) {
                DB::table('tramites')->where('clave', $clave)->update($data);
            } else {
                DB::table('tramites')->insert($data + ['clave' => $clave, 'created_at' => now()]);
            }
        }
    }
}
