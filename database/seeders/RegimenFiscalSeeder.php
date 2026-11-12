<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegimenFiscalSeeder extends Seeder
{
    /**
     * Catálogo de regímenes fiscales (claves SAT).
     */
    public function run(): void
    {
        $regimenes = [
            ['clave' => '601', 'nombre' => 'Sueldos y salarios'],
            ['clave' => '603', 'nombre' => 'Actividades empresariales y profesionales (régimen integrador)'],
            ['clave' => '605', 'nombre' => 'Actividades empresariales y profesionales (renta presunta)'],
            ['clave' => '606', 'nombre' => 'Arrendamiento'],
            ['clave' => '607', 'nombre' => 'Enajenación de bienes'],
            ['clave' => '608', 'nombre' => 'Adquisición de bienes'],
            ['clave' => '609', 'nombre' => 'Otros ingresos'],
            ['clave' => '610', 'nombre' => 'Dividendos'],
            ['clave' => '611', 'nombre' => 'Personas físicas con actividad empresarial'],
            ['clave' => '612', 'nombre' => 'Personas físicas con actividades empresariales y profesionales (disposiciones transitorias)'],
            ['clave' => '614', 'nombre' => 'Ingresos por intereses'],
            ['clave' => '615', 'nombre' => 'Régimen de los bienes que constan en la escritura o acta constitutiva'],
            ['clave' => '616', 'nombre' => 'Sin obligaciones fiscales'],
            ['clave' => '620', 'nombre' => 'Sociedades mercantiles de personas físicas con fin no lucrativo'],
            ['clave' => '621', 'nombre' => 'Incorporación fiscal'],
            ['clave' => '622', 'nombre' => 'Actividades agrícolas, ganaderas, silvícolas y pesqueras'],
            ['clave' => '623', 'nombre' => 'Opcional para grupos de sociedades'],
            ['clave' => '624', 'nombre' => 'Coordenadas'],
            ['clave' => '625', 'nombre' => 'Régimen de las actividades empresariales con ingresos a través de plataformas tecnológicas'],
            ['clave' => '626', 'nombre' => 'Régimen simplificado de confianza'],
            ['clave' => '628', 'nombre' => 'Régimen de persona moral con fin no lucrativo'],
            ['clave' => '629', 'nombre' => 'Sociedades y asociaciones civiles, cooperativas y privada de inversión'],
            ['clave' => '630', 'nombre' => 'Actividades empresariales y profesionales'],
            ['clave' => '651', 'nombre' => 'Régimen simplificado de incorporación fiscal para personas morales de bajo ingreso'],
            ['clave' => '652', 'nombre' => 'Coordinados'],
            ['clave' => '653', 'nombre' => 'Régimen de fusiones y escisiones'],
            ['clave' => '654', 'nombre' => 'Regímenes fiscales preferentes'],
        ];

        foreach ($regimenes as $regimen) {
            $existente = DB::table('regimenes_fiscales')->where('clave', $regimen['clave'])->first();
            if ($existente) {
                DB::table('regimenes_fiscales')->where('id', $existente->id)->update(['nombre' => $regimen['nombre'], 'activo' => true, 'updated_at' => now()]);

                continue;
            }
            DB::table('regimenes_fiscales')->insert($regimen + ['id' => (string) Str::uuid(), 'activo' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
