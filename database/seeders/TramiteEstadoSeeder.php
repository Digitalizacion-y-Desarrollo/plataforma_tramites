<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TramiteEstadoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['BORRADOR', 'Borrador', 1], ['PUBLICADO', 'Publicado', 2], ['ARCHIVADO', 'Archivado', 3]] as [$clave, $nombre, $orden]) {
            DB::table('tramite_estados')->updateOrInsert(['clave' => $clave], [
                'nombre' => $nombre,
                'orden' => $orden,
                'activo' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }
}
