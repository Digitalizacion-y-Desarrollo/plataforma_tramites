<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MunicipioSeeder extends Seeder
{
    /**
     * Catálogo provisional de municipios para el select de seguimiento.
     * Posteriormente el catálogo se consultará desde una API externa.
     */
    public function run(): void
    {
        $municipios = [
            ['nombre' => 'Nezahualcóyotl', 'estado' => 'Estado de México'],
            ['nombre' => 'Chimalhuacán', 'estado' => 'Estado de México'],
            ['nombre' => 'Ecatepec de Morelos', 'estado' => 'Estado de México'],
            ['nombre' => 'Tlalnepantla de Baz', 'estado' => 'Estado de México'],
            ['nombre' => 'Naucalpan de Juárez', 'estado' => 'Estado de México'],
            ['nombre' => 'Toluca', 'estado' => 'Estado de México'],
            ['nombre' => 'Ciudad de México', 'estado' => 'Distrito Federal'],
            ['nombre' => 'Otro municipio', 'estado' => null],
        ];

        foreach ($municipios as $municipio) {
            $existente = DB::table('municipios')->where('nombre', $municipio['nombre'])->first();
            if ($existente) {
                DB::table('municipios')->where('id', $existente->id)->update(['estado' => $municipio['estado'], 'activo' => true, 'updated_at' => now()]);

                continue;
            }
            DB::table('municipios')->insert($municipio + ['id' => (string) Str::uuid(), 'activo' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
