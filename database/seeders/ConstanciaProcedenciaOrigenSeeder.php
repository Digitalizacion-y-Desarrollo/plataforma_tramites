<?php

namespace Database\Seeders;

use App\Models\Formulario;
use App\Models\FormularioCampo;
use App\Models\FormularioEstado;
use App\Models\FormularioSeccion;
use App\Models\FormularioVersion;
use App\Models\Tramite;
use App\Models\TramiteEstado;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConstanciaProcedenciaOrigenSeeder extends Seeder
{
    private const TRAMITE_CLAVE = 'CONST_PROCEDENCIA_ORIGEN';

    private const FORMULARIO_NOMBRE = 'Constancia de procedencia u origen 2026';

    private const DEPARTAMENTO_ID = '30';

    private const DEPARTAMENTO_NOMBRE = 'SECRETARÍA DEL H. AYUNTAMIENTO';

    public function run(): void
    {
        $this->call([TramiteEstadoSeeder::class, FormularioEstadoSeeder::class]);

        DB::transaction(function (): void {
            $tramite = $this->sembrarTramite();
            $version = $this->sembrarVersion($this->sembrarFormulario());
            $this->sembrarEstructura($version);
            $this->sembrarCapturaActaNacimiento($version);
            $this->asignarVersion($tramite, $version);
        });
    }

    private function sembrarTramite(): Tramite
    {
        $tramite = Tramite::withTrashed()->firstOrNew(['clave' => self::TRAMITE_CLAVE]);
        $tramite->fill([
            'nombre' => 'Constancia de procedencia u origen',
            'descripcion' => 'Constancia exclusiva para personas originarias o residentes del Municipio de Nezahualcóyotl que acrediten su procedencia u origen.',
            'departamento_accesos_id' => self::DEPARTAMENTO_ID,
            'departamento_nombre_snapshot' => self::DEPARTAMENTO_NOMBRE,
            'duracion' => 'Máximo 24 horas hábiles', 'costo' => '$117.31 MXN', 'costo_pago' => 117.31,
            'flujo_pago' => 'SIN_PAGO', 'pago_vigencia_dias' => null, 'modalidades' => ['PRESENCIAL'],
            'requisitos' => [
                'Acta de nacimiento actualizada y en buen estado del (la) solicitante, donde demuestre ser originario o registrado en el Municipio de Nezahualcóyotl.',
                'Comprobante de domicilio reciente no mayor a tres meses de antigüedad: recibo de luz, teléfono, pago de predio, pago de agua, aforo o correspondencia bancaria a nombre del (la) solicitante o que coincida con los datos de la credencial para votar de sus testigos. Los comprobantes digitales deben imprimirse con firma, sello digital y código QR. No se aceptan impresiones de Totalplay ni de izzi. Si la dirección está oculta en la credencial, presentar comprobante de domicilio a su nombre.',
                'Dos fotografías recientes del (la) solicitante de estudio fotográfico, tamaño pasaporte, impresas en papel fotográfico mate y sin accesorios. No se aceptan fotos de celular o digitales ni con fondos de color.',
                'Si el (la) solicitante es niño, niña o adolescente, los testigos deberán ser necesariamente y exclusivamente sus padres; presentar la documentación indicada para los testigos.',
                'Dos testigos consanguíneos que coincidan con los apellidos del solicitante, con credencial para votar vigente y actualizada, acta de nacimiento y comprobante de domicilio reciente no mayor a tres meses que coincida con los datos de su credencial. Si la dirección está oculta, presentar comprobante de domicilio a su nombre. Los comprobantes digitales deben imprimirse con firma, sello digital y código QR. No se aceptan impresiones de Totalplay ni de izzi.',
                'Si el (la) solicitante ha sido residente del Municipio de Nezahualcóyotl y esta sea su última procedencia, presentar documentos que lo acrediten, por ejemplo: credenciales para votar, acta de matrimonio del Municipio de Nezahualcóyotl, actas de nacimiento de hijos registrados en el Municipio de Nezahualcóyotl, entre otros.',
                'El trámite de Constancia de Procedencia u Origen es sólo para los originarios o residentes del Municipio de Nezahualcóyotl que así lo acrediten.',
            ],
            'proceso' => ['Captura la solicitud en línea y adjunta los documentos requeridos.', 'El área responsable revisa la información y documentación.', 'Acude a la Secretaría del H. Ayuntamiento con los originales y copias cuando te sea indicado.', 'La entrega de la constancia es en un máximo de 24 horas hábiles después de haber realizado su trámite.'],
            'preguntas_frecuentes' => [['pregunta' => '¿Cuál es la vigencia de la constancia?', 'respuesta' => 'La vigencia de la constancia es de 90 días corrientes.'], ['pregunta' => '¿Cuál es el horario de atención?', 'respuesta' => 'De lunes a viernes de 9:00 a 16:00 horas; la documentación se recibe antes de las 15:30 horas.']],
            'activo' => true, 'tramite_estado_id' => TramiteEstado::query()->where('clave', 'PUBLICADO')->where('activo', true)->value('id'),
        ]);
        $tramite->deleted_at = null;
        $tramite->save();

        return $tramite;
    }

    private function sembrarFormulario(): Formulario
    {
        $formulario = Formulario::withTrashed()->firstOrNew(['nombre' => self::FORMULARIO_NOMBRE, 'departamento_accesos_id' => self::DEPARTAMENTO_ID]);
        if (! $formulario->exists) {
            $formulario->id = (string) Str::uuid();
        }
        $formulario->fill(['descripcion' => 'Datos del solicitante, de dos testigos consanguíneos y documentos para la Constancia de Procedencia u Origen.', 'departamento_nombre_snapshot' => self::DEPARTAMENTO_NOMBRE, 'subdepartamento_accesos_id' => null, 'subdepartamento_nombre_snapshot' => null, 'creado_por_accesos_id' => null]);
        $formulario->deleted_at = null;
        $formulario->save();

        return $formulario;
    }

    private function sembrarVersion(Formulario $formulario): FormularioVersion
    {
        $estadoPublicado = FormularioEstado::query()->where('clave', 'PUBLICADO')->where('activo', true)->firstOrFail();
        $version = FormularioVersion::query()->firstOrNew(['formulario_id' => $formulario->id, 'numero_version' => 1]);
        if (! $version->exists) {
            $version->id = (string) Str::uuid();
        }
        $version->fill(['formulario_estado_id' => $estadoPublicado->id, 'fecha_publicacion' => $version->fecha_publicacion ?? now()])->save();

        return $version;
    }

    private function sembrarEstructura(FormularioVersion $version): void
    {
        if (DB::table('solicitudes')->where('formulario_version_id', $version->id)->exists()) {
            $this->command?->warn('La versión 1 de la constancia de procedencia u origen ya tiene solicitudes; no se modificó su estructura.');

            return;
        }
        foreach ($this->secciones() as $ordenSeccion => $definicionSeccion) {
            $seccion = FormularioSeccion::query()->firstOrNew(['formulario_version_id' => $version->id, 'clave' => $definicionSeccion['clave']]);
            if (! $seccion->exists) {
                $seccion->id = (string) Str::uuid();
            }
            $seccion->fill(['titulo' => $definicionSeccion['titulo'], 'descripcion' => $definicionSeccion['descripcion'] ?? null, 'orden' => $ordenSeccion + 1, 'activo' => true])->save();
            if ($definicionSeccion['clave'] === 'datos_solicitante') {
                FormularioCampo::query()
                    ->where('seccion_id', $seccion->id)
                    ->whereIn('clave', ['nombres', 'paterno', 'materno', 'calle', 'col', 'municipio', 'cp', 'identificacion'])
                    ->delete();
            }
            if ($definicionSeccion['clave'] === 'testigo_1') {
                FormularioCampo::query()
                    ->where('seccion_id', $seccion->id)
                    ->whereIn('clave', ['t1_calle', 't1_col', 't1_municipio', 't1_cp', 'tipo_identificacion_t1'])
                    ->delete();
            }
            if ($definicionSeccion['clave'] === 'testigo_2') {
                FormularioCampo::query()
                    ->where('seccion_id', $seccion->id)
                    ->whereIn('clave', ['t2_calle', 't2_col', 't2_municipio', 't2_cp', 'tipo_identificacion_t2'])
                    ->delete();
            }
            foreach ($definicionSeccion['campos'] as $ordenCampo => $definicionCampo) {
                $campo = FormularioCampo::query()->firstOrNew(['seccion_id' => $seccion->id, 'clave' => $definicionCampo['clave']]);
                if (! $campo->exists) {
                    $campo->id = (string) Str::uuid();
                }
                $campo->fill(['etiqueta' => $definicionCampo['etiqueta'], 'tipo' => $definicionCampo['tipo'], 'requerido' => $definicionCampo['requerido'] ?? true, 'orden' => $ordenCampo + 1, 'configuracion' => $definicionCampo['configuracion'] ?? null, 'activo' => true])->save();
            }
        }
    }

    private function asignarVersion(Tramite $tramite, FormularioVersion $version): void
    {
        DB::table('tramite_formulario')->where('tramite_id', $tramite->id)->where('activo', true)->where('formulario_version_id', '!=', $version->id)->update(['activo' => false, 'fecha_fin_vigencia' => now(), 'updated_at' => now()]);
        $vinculo = DB::table('tramite_formulario')->where('tramite_id', $tramite->id)->where('formulario_version_id', $version->id)->first();
        if ($vinculo) {
            DB::table('tramite_formulario')->where('id', $vinculo->id)->update(['activo' => true, 'fecha_fin_vigencia' => null, 'updated_at' => now()]);

            return;
        }
        DB::table('tramite_formulario')->insert(['id' => (string) Str::uuid(), 'tramite_id' => $tramite->id, 'formulario_version_id' => $version->id, 'fecha_inicio_vigencia' => now(), 'fecha_fin_vigencia' => null, 'activo' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function sembrarCapturaActaNacimiento(FormularioVersion $version): void
    {
        $campoFuenteId = DB::table('formulario_campos as campo')
            ->join('formulario_secciones as seccion', 'seccion.id', '=', 'campo.seccion_id')
            ->where('seccion.formulario_version_id', $version->id)
            ->where('campo.clave', 'acta_nacimiento')
            ->value('campo.id');

        if (! $campoFuenteId) {
            return;
        }

        $captura = DB::table('formulario_capturas_administrativas')
            ->where('formulario_version_id', $version->id)
            ->where('clave', 'datos_acta_nacimiento')
            ->first();

        if ($captura && DB::table('solicitudes')->where('formulario_version_id', $version->id)->exists()) {
            return;
        }

        $capturaId = $captura?->id ?? (string) Str::uuid();
        $datosCaptura = [
            'campo_fuente_id' => $campoFuenteId,
            'titulo' => 'Datos extraídos del acta de nacimiento',
            'descripcion' => 'Captura la información verificada directamente en el acta de nacimiento del solicitante.',
            'orden' => 1,
            'activo' => true,
            'updated_at' => now(),
        ];

        if ($captura) {
            DB::table('formulario_capturas_administrativas')->where('id', $capturaId)->update($datosCaptura);
        } else {
            DB::table('formulario_capturas_administrativas')->insert($datosCaptura + [
                'id' => $capturaId,
                'formulario_version_id' => $version->id,
                'clave' => 'datos_acta_nacimiento',
                'created_at' => now(),
            ]);
        }

        foreach ($this->camposCapturaActaNacimiento() as $orden => $campo) {
            $existente = DB::table('formulario_captura_administrativa_campos')
                ->where('captura_administrativa_id', $capturaId)
                ->where('clave', $campo['clave'])
                ->first();
            $datosCampo = [
                'etiqueta' => $campo['etiqueta'],
                'tipo' => $campo['tipo'] ?? 'TEXT',
                'requerido' => $campo['requerido'],
                'orden' => $orden + 1,
                'configuracion' => null,
                'activo' => true,
                'updated_at' => now(),
            ];
            if ($existente) {
                DB::table('formulario_captura_administrativa_campos')->where('id', $existente->id)->update($datosCampo);
            } else {
                DB::table('formulario_captura_administrativa_campos')->insert($datosCampo + [
                    'id' => (string) Str::uuid(),
                    'captura_administrativa_id' => $capturaId,
                    'clave' => $campo['clave'],
                    'created_at' => now(),
                ]);
            }
        }
    }

    private function camposCapturaActaNacimiento(): array
    {
        $letras = ['no_acta_letra', 'no_oficialia_letra', 'no_libro_letra', 'dia_nacimiento_letra', 'ano_nacimiento_letra', 'dia_certificacion_letra', 'ano_certificacion_letra'];
        $campos = [
            'lugar_nacimiento' => 'Lugar de nacimiento', 'lugar_registro' => 'Lugar de registro', 'numero_acta' => 'Número de acta', 'no_acta_letra' => 'No. acta letra', 'no_oficialia' => 'No. oficialía', 'no_oficialia_letra' => 'No. oficialía letra', 'no_libro' => 'No. libro', 'no_libro_letra' => 'No. libro letra', 'dia_nacimiento' => 'Día de nacimiento', 'dia_nacimiento_letra' => 'Día nacimiento letra', 'mes_nacimiento' => 'Mes de nacimiento', 'ano_nacimiento' => 'Año de nacimiento', 'ano_nacimiento_letra' => 'Año nacimiento letra', 'nombre_padre' => 'Nombre del padre', 'nombre_madre' => 'Nombre de madre', 'tiempo_residencia' => 'Tiempo de residencia', 'domicilio_residencia' => 'Domicilio de residencia', 'nombre_certifica' => 'Nombre de quien certifica', 'cargo' => 'Cargo', 'dia_certificacion' => 'Día de certificación', 'dia_certificacion_letra' => 'Día certificación letra', 'mes_certificacion' => 'Mes de certificación', 'ano_certificacion' => 'Año certificación', 'ano_certificacion_letra' => 'Año certificación letra',
        ];

        return collect($campos)->map(fn (string $etiqueta, string $clave) => [
            'clave' => $clave,
            'etiqueta' => $etiqueta,
            'tipo' => $clave === 'domicilio_residencia' ? 'DOMICILIO' : 'TEXT',
            'requerido' => ! in_array($clave, $letras, true),
        ])->values()->all();
    }

    private function secciones(): array
    {
        return [
            ['clave' => 'datos_solicitante', 'titulo' => 'Datos del solicitante', 'campos' => [
                ['clave' => 'domicilio', 'etiqueta' => 'Domicilio en Nezahualcóyotl', 'tipo' => 'DOMICILIO'], ['clave' => 'no_identificacion', 'etiqueta' => 'No. de identificación de la INE (IDMEX seguido de 10 números)', 'tipo' => 'TEXT'], ['clave' => 'tramite', 'etiqueta' => '¿Para qué requiere este documento?', 'tipo' => 'TEXT'], ['clave' => 'fecha_nacimiento', 'etiqueta' => 'Fecha de nacimiento', 'tipo' => 'DATE'],
            ]],
            ['clave' => 'testigo_1', 'titulo' => 'Testigo 1', 'descripcion' => 'Debe ser testigo consanguíneo; si el solicitante es menor de edad, deberá ser su padre o madre.', 'campos' => [
                ['clave' => 't1_nombre', 'etiqueta' => 'Nombre(s)', 'tipo' => 'TEXT'], ['clave' => 't1_paterno', 'etiqueta' => 'Apellido paterno', 'tipo' => 'TEXT'], ['clave' => 't1_materno', 'etiqueta' => 'Apellido materno', 'tipo' => 'TEXT'], ['clave' => 't1_domicilio', 'etiqueta' => 'Domicilio en Nezahualcóyotl', 'tipo' => 'DOMICILIO'], ['clave' => 'id_t1', 'etiqueta' => 'No. de identificación de la INE (IDMEX seguido de 10 números)', 'tipo' => 'TEXT'], ['clave' => 'parentesco_t1', 'etiqueta' => 'Parentesco', 'tipo' => 'TEXT'],
            ]],
            ['clave' => 'testigo_2', 'titulo' => 'Testigo 2', 'descripcion' => 'Debe ser testigo consanguíneo; si el solicitante es menor de edad, deberá ser su padre o madre.', 'campos' => [
                ['clave' => 't2_nombre', 'etiqueta' => 'Nombre(s)', 'tipo' => 'TEXT'], ['clave' => 't2_paterno', 'etiqueta' => 'Apellido paterno', 'tipo' => 'TEXT'], ['clave' => 't2_materno', 'etiqueta' => 'Apellido materno', 'tipo' => 'TEXT'], ['clave' => 't2_domicilio', 'etiqueta' => 'Domicilio en Nezahualcóyotl', 'tipo' => 'DOMICILIO'], ['clave' => 'id_t2', 'etiqueta' => 'No. de identificación de la INE (IDMEX seguido de 10 números)', 'tipo' => 'TEXT'], ['clave' => 'parentesco_t2', 'etiqueta' => 'Parentesco', 'tipo' => 'TEXT'],
            ]],
            ['clave' => 'documentos_solicitante', 'titulo' => 'Documentos del solicitante', 'descripcion' => 'Adjunta archivos PDF, JPG, PNG, DOC o DOCX de hasta 10 MB.', 'campos' => [
                ['clave' => 'acta_nacimiento', 'etiqueta' => 'Acta de nacimiento actualizada', 'tipo' => 'FILE'], ['clave' => 'comprobante_domicilio', 'etiqueta' => 'Comprobante de domicilio (no mayor a 3 meses)', 'tipo' => 'FILE'], ['clave' => 'fotografias', 'etiqueta' => 'Dos fotografías tamaño pasaporte, en papel fotográfico mate', 'tipo' => 'FILE'], ['clave' => 'acreditacion_procedencia', 'etiqueta' => 'Documento que acredita procedencia o residencia en Nezahualcóyotl, cuando corresponda', 'tipo' => 'FILE', 'requerido' => false],
            ]],
            ['clave' => 'documentos_testigos', 'titulo' => 'Documentos de los testigos', 'campos' => [
                ['clave' => 't1_credencial_votar', 'etiqueta' => 'Credencial para votar vigente del testigo 1', 'tipo' => 'FILE'], ['clave' => 't1_acta_nacimiento', 'etiqueta' => 'Acta de nacimiento del testigo 1', 'tipo' => 'FILE'], ['clave' => 't1_comprobante_domicilio', 'etiqueta' => 'Comprobante de domicilio del testigo 1 (no mayor a 3 meses)', 'tipo' => 'FILE'], ['clave' => 't2_credencial_votar', 'etiqueta' => 'Credencial para votar vigente del testigo 2', 'tipo' => 'FILE'], ['clave' => 't2_acta_nacimiento', 'etiqueta' => 'Acta de nacimiento del testigo 2', 'tipo' => 'FILE'], ['clave' => 't2_comprobante_domicilio', 'etiqueta' => 'Comprobante de domicilio del testigo 2 (no mayor a 3 meses)', 'tipo' => 'FILE'],
            ]],
        ];
    }
}
