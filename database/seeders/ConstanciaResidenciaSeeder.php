<?php

namespace Database\Seeders;

use App\Models\CampoCondicion;
use App\Models\CampoOpcion;
use App\Models\Formulario;
use App\Models\FormularioCampo;
use App\Models\FormularioEstado;
use App\Models\FormularioSeccion;
use App\Models\Tramite;
use App\Models\TramiteEstado;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ConstanciaResidenciaSeeder extends Seeder
{
    private const CLAVE = 'CONST_RESIDENCIA';

    private const NOMBRE = 'Constancia de residencia 2026';

    public function run(): void
    {
        $this->call([FormularioEstadoSeeder::class, TramiteEstadoSeeder::class]);

        DB::transaction(function (): void {
            $existente = Tramite::withTrashed()->where('clave', self::CLAVE)->exists();
            $formularioExistente = Formulario::withTrashed()->where('nombre', self::NOMBRE)->where('departamento_accesos_id', '30')->exists();

            if ($existente && $formularioExistente) {
                $this->command?->warn('La constancia de residencia ya existe; no se modificaron sus datos ni versiones.');

                return;
            }

            if ($existente || $formularioExistente) {
                throw new RuntimeException('La constancia de residencia existe parcialmente; revisa sus registros antes de ejecutar el seeder.');
            }

            $formulario = Formulario::create([
                'id' => (string) Str::uuid(),
                'nombre' => self::NOMBRE,
                'descripcion' => 'Datos del solicitante, propósito de la constancia y testigos cuando corresponda.',
                'departamento_accesos_id' => '30',
                'departamento_nombre_snapshot' => 'SECRETARÍA DEL H. AYUNTAMIENTO',
            ]);
            $version = $formulario->versiones()->create([
                'id' => (string) Str::uuid(),
                'numero_version' => 1,
                'formulario_estado_id' => FormularioEstado::where('clave', 'BORRADOR')->where('activo', true)->firstOrFail()->id,
            ]);

            $campos = [];
            foreach ($this->secciones() as $indice => $definicion) {
                $seccion = FormularioSeccion::create([
                    'id' => (string) Str::uuid(), 'formulario_version_id' => $version->id,
                    'clave' => $definicion['clave'], 'titulo' => $definicion['titulo'],
                    'descripcion' => $definicion['descripcion'] ?? null, 'orden' => $indice + 1, 'activo' => true,
                ]);
                foreach ($definicion['campos'] as $orden => $campo) {
                    $campos[$campo[0]] = FormularioCampo::create([
                        'id' => (string) Str::uuid(), 'seccion_id' => $seccion->id,
                        'clave' => $campo[0], 'etiqueta' => $campo[1], 'tipo' => $campo[2] ?? 'TEXT',
                        'requerido' => $campo[3] ?? false, 'orden' => $orden + 1, 'activo' => true,
                    ]);
                }
            }

            foreach (['Sí', 'No'] as $indice => $etiqueta) {
                CampoOpcion::create(['id' => (string) Str::uuid(), 'campo_id' => $campos['requiere_testigos']->id, 'valor' => (string) ($indice + 1), 'etiqueta' => $etiqueta, 'orden' => $indice + 1, 'activo' => true]);
            }

            foreach ($campos as $clave => $campo) {
                if (! str_starts_with($clave, 't1_') && ! str_starts_with($clave, 't2_')) {
                    continue;
                }
                foreach (str_ends_with($clave, '_materno') ? ['SHOW'] : ['SHOW', 'REQUIRE'] as $accion) {
                    CampoCondicion::create(['id' => (string) Str::uuid(), 'campo_destino_id' => $campo->id, 'campo_origen_id' => $campos['requiere_testigos']->id, 'operador' => 'EQUALS', 'accion' => $accion, 'valor_comparacion' => ['valor' => 'Sí']]);
                }
            }

            $tramite = Tramite::create([
                'clave' => self::CLAVE, 'nombre' => self::NOMBRE,
                'descripcion' => 'Constancia de residencia para habitantes del Municipio de Nezahualcóyotl. El trámite es personal.',
                'departamento_accesos_id' => '30', 'departamento_nombre_snapshot' => 'SECRETARÍA DEL H. AYUNTAMIENTO',
                'duracion' => 'Máximo 24 horas hábiles', 'costo' => '$117.31 MXN', 'costo_pago' => 117.31,
                'modalidades' => ['PRESENCIAL'],
                'requisitos' => [
                    'El solicitante y, en su caso, los testigos deberán presentarse con documentación en original y copia.',
                    'Identificación oficial del solicitante con fotografía: credencial para votar, cartilla militar o pasaporte, vigente, actualizada y en buen estado.',
                    'Comprobante de domicilio del solicitante no mayor a tres meses, a su nombre o coincidente con su credencial para votar: luz, teléfono, predio, agua, aforo o correspondencia bancaria. Si es digital, debe imprimirse con firma, sello digital y código QR. No se aceptan impresiones de Totalplay ni de izzi.',
                    'Una fotografía reciente de frente, tamaño infantil, impresa en papel fotográfico mate y sin accesorios. No se aceptan fotos de celular o digitales ni fondos de color.',
                    'Si la credencial para votar está vigente pero no actualizada, los datos no coinciden con el comprobante y no se cuenta con comprobante a nombre del solicitante, o si la dirección está oculta en la credencial, presentar dos testigos con credencial vigente y actualizada y comprobante de domicilio no mayor a tres meses.',
                    'El primer testigo debe vivir en la misma casa y número oficial; el segundo debe vivir en la misma calle, pero no en la misma casa ni número oficial. Si su dirección está oculta en la credencial, deben presentar comprobante vigente a su nombre.',
                    'Los comprobantes digitales de los testigos deben imprimirse con firma, sello digital y código QR. No se aceptan comprobantes de Totalplay ni de izzi.',
                    'El trámite es exclusivo para habitantes del Municipio de Nezahualcóyotl que acrediten su residencia.',
                ],
                'proceso' => ['El trámite es personal y debe presentarse la documentación en original y copia.', 'La entrega de la constancia es en un máximo de 24 horas hábiles después de realizar el trámite.'],
                'preguntas_frecuentes' => [
                    ['pregunta' => '¿Cuál es la vigencia de la constancia?', 'respuesta' => '45 días corrientes.'],
                    ['pregunta' => '¿Cuál es el horario de atención?', 'respuesta' => 'De lunes a viernes de 9:00 a 16:00 para informes y de 9:00 a 15:30 para realizar el trámite.'],
                ],
                'activo' => true, 'tramite_estado_id' => TramiteEstado::where('clave', 'BORRADOR')->where('activo', true)->firstOrFail()->id,
            ]);

            DB::table('tramite_formulario')->insert([
                'id' => (string) Str::uuid(), 'tramite_id' => $tramite->id, 'formulario_version_id' => $version->id,
                'fecha_inicio_vigencia' => now(), 'activo' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    private function secciones(): array
    {
        $testigo = fn (string $prefijo): array => [
            [$prefijo.'_nombre', 'Nombre(s)'], [$prefijo.'_paterno', 'Apellido paterno'], [$prefijo.'_materno', 'Apellido materno'],
            [$prefijo.'_calle', 'Calle'], [$prefijo.'_col', 'Colonia'], [$prefijo.'_municipio', 'Municipio'], [$prefijo.'_cp', 'Código postal'],
        ];

        return [
            ['clave' => 'solicitante', 'titulo' => 'Datos del solicitante', 'campos' => [
                ['calle', 'Calle', 'TEXT', true], ['col', 'Colonia', 'TEXT', true], ['municipio', 'Municipio', 'TEXT', true], ['cp', 'Código postal', 'TEXT', true],
                ['tramite', '¿Para qué requiere la constancia?', 'TEXT', true],
                ['requiere_testigos', '¿Requiere testigos conforme a los requisitos de domicilio y credencial?', 'RADIO', true],
            ]],
            ['clave' => 'testigo_1', 'titulo' => 'Primer testigo', 'descripcion' => 'Debe vivir en la misma casa y número oficial del solicitante.', 'campos' => $testigo('t1')],
            ['clave' => 'testigo_2', 'titulo' => 'Segundo testigo', 'descripcion' => 'Debe vivir en la misma calle, pero no en la misma casa ni número oficial del solicitante.', 'campos' => $testigo('t2')],
        ];
    }
}
