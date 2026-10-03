<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TramiteSeeder extends Seeder
{
    public function run(): void
    {
        $estadoPublicadoId = DB::table('tramite_estados')->where('clave', 'PUBLICADO')->value('id');

        foreach ([['CONST_REG', 'Constancia de regularización', null], ['TERM_OBRA', 'Constancia de terminación de obra', null], ['REG_CONDOMINIO', 'Constancia de cambio o régimen en condominio', null], ['SUSP_OBRA', 'Constancia de suspensión de obra', null], ['PREDIAL', 'Pago de predial', ['descripcion' => 'Consulta y realiza el pago del impuesto predial de tu propiedad.', 'duracion' => 'Ejercicio fiscal vigente', 'costo' => 'Con costo', 'tiempo_estimado' => '10 minutos en línea', 'modalidades' => json_encode(['EN_LINEA', 'PRESENCIAL']), 'requisitos' => json_encode(['Clave catastral o cuenta predial del inmueble.', 'Correo electrónico para recibir avisos y comprobantes.']), 'proceso' => json_encode(['Ingresa tu cuenta predial', 'Revisa inmueble y adeudo', 'Selecciona método de pago', 'Descarga tu comprobante']), 'preguntas_frecuentes' => json_encode([['pregunta' => '¿Cómo encuentro la cuenta predial?', 'respuesta' => 'Puedes localizarla en recibos anteriores, estados de cuenta o documentos relacionados con el inmueble.']])]]] as [$clave,$nombre,$detalle]) {
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
