<?php

namespace Database\Seeders;

use App\Models\CampoCondicion;
use App\Models\CampoOpcion;
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
use RuntimeException;

class ConstanciaIdentidadSeeder extends Seeder
{
    private const TRAMITE_CLAVE = 'CONST_IDENTIDAD';

    private const DEPARTAMENTO_ID = '30';

    private const DEPARTAMENTO_NOMBRE = 'SECRETARÍA DEL H. AYUNTAMIENTO';

    private const FORMULARIO_NOMBRE = 'Constancia de identidad 2026';

    public function run(): void
    {
        $this->call([
            TramiteEstadoSeeder::class,
            FormularioEstadoSeeder::class,
        ]);

        DB::transaction(function (): void {
            $tramite = $this->sembrarTramite();
            $formulario = $this->sembrarFormulario();
            $version = $this->sembrarVersion($formulario);

            $this->sembrarEstructura($version);
            $this->asignarVersion($tramite, $version);
        });
    }

    private function sembrarTramite(): Tramite
    {
        $estadoPublicado = TramiteEstado::query()
            ->where('clave', 'PUBLICADO')
            ->where('activo', true)
            ->firstOrFail();

        $tramite = Tramite::withTrashed()->firstOrNew(['clave' => self::TRAMITE_CLAVE]);

        $tramite->fill([
            'nombre' => self::FORMULARIO_NOMBRE,
            'descripcion' => 'Constancia de identidad para habitantes del Municipio de Nezahualcóyotl. El trámite es personal y requiere dos testigos consanguíneos con documentación vigente.',
            'departamento_accesos_id' => self::DEPARTAMENTO_ID,
            'departamento_nombre_snapshot' => self::DEPARTAMENTO_NOMBRE,
            'duracion' => '24 horas hábiles',
            'costo' => '$117.31 MXN',
            'costo_pago' => 117.31,
            'flujo_pago' => 'PAGO_POST_VALIDACION',
            'pago_vigencia_dias' => 30,
            'tiempo_estimado' => null,
            'modalidades' => ['PRESENCIAL'],
            'requisitos' => [
                'Acta de nacimiento del interesado(a) actualizada y en buen estado. En caso de registro extemporáneo, presentar las constancias de no registro del Registro Civil de Nezahualcóyotl, Chimalhuacán y de la Ciudad de México.',
                'Comprobante de domicilio reciente no mayor a tres meses a nombre del solicitante o que coincida con sus datos.',
                'Una fotografía de frente reciente, tamaño infantil, impresa en papel fotográfico mate, fondo blanco y sin accesorios.',
                'Dos testigos consanguíneos con credencial para votar vigente y comprobante de domicilio reciente no mayor a tres meses.',
                'Si el solicitante es niño, niña o adolescente, los testigos deberán ser su padre y su madre. Si alguno falleció, presentar acta de defunción y otro familiar directo.',
                'El trámite es exclusivo para habitantes del Municipio de Nezahualcóyotl, lo que deberá acreditarse.',
                'Si la constancia es para un familiar hospitalizado, presentar constancia de hospitalización emitida por el hospital, sellada y firmada por Trabajo Social.',
            ],
            'proceso' => [
                'Captura tu solicitud en línea y adjunta los documentos requeridos.',
                'El área responsable revisa y valida tu información y documentos.',
                'Recibe por correo tu ficha de pago y acude a pagar el importe de $117.31 MXN antes de la fecha de vencimiento.',
                'Sube tu comprobante de pago en la consulta de tu trámite para que el área responsable lo valide.',
                'Una vez validado el pago, acude a la Secretaría del H. Ayuntamiento con los originales y copias.',
                'Recibe tu constancia en un máximo de 24 horas hábiles.',
            ],
            'preguntas_frecuentes' => [
                ['pregunta' => '¿Cuál es el costo del trámite?', 'respuesta' => 'El costo es de $117.31 MXN, de acuerdo con el Código Financiero del Estado de México y sus Municipios, artículo 147 fracción V.'],
                ['pregunta' => '¿Cómo se realiza el pago?', 'respuesta' => 'Una vez validada tu documentación recibirás por correo tu ficha de pago. Debes pagarla y subir el comprobante en la consulta de tu trámite para que el área responsable valide el pago.'],
                ['pregunta' => '¿Cuánto tarda la entrega?', 'respuesta' => 'La entrega es en un máximo de 24 horas hábiles después de haber realizado el trámite.'],
                ['pregunta' => '¿Cuánto tiempo es válida la constancia?', 'respuesta' => 'La vigencia de la constancia es de 45 días naturales.'],
                ['pregunta' => '¿Puedo hacer el trámite en línea?', 'respuesta' => 'Puedes capturar la solicitud en línea, pero el trámite es personal: deberás acudir con los originales y copias.'],
            ],
            'descarga_expira_dias' => null,
            'activo' => true,
            'tramite_estado_id' => $estadoPublicado->id,
        ]);

        $tramite->deleted_at = null;
        $tramite->save();

        return $tramite;
    }

    private function sembrarFormulario(): Formulario
    {
        $formulario = Formulario::withTrashed()->firstOrNew([
            'nombre' => self::FORMULARIO_NOMBRE,
            'departamento_accesos_id' => self::DEPARTAMENTO_ID,
        ]);

        if (! $formulario->exists) {
            $formulario->id = (string) Str::uuid();
        }

        $formulario->fill([
            'descripcion' => 'Datos del solicitante, domicilio, testigos, documentos y casos especiales.',
            'departamento_nombre_snapshot' => self::DEPARTAMENTO_NOMBRE,
            'subdepartamento_accesos_id' => null,
            'subdepartamento_nombre_snapshot' => null,
            'creado_por_accesos_id' => null,
        ]);

        $formulario->deleted_at = null;
        $formulario->save();

        return $formulario;
    }

    private function sembrarVersion(Formulario $formulario): FormularioVersion
    {
        $estadoBorrador = FormularioEstado::query()
            ->where('clave', 'BORRADOR')
            ->where('activo', true)
            ->firstOrFail();

        $version = FormularioVersion::query()->firstOrNew([
            'formulario_id' => $formulario->id,
            'numero_version' => 1,
        ]);

        if (! $version->exists) {
            $version->id = (string) Str::uuid();
            $version->formulario_estado_id = $estadoBorrador->id;
        }

        $version->save();

        return $version;
    }

    private function sembrarEstructura(FormularioVersion $version): void
    {
        if (DB::table('solicitudes')->where('formulario_version_id', $version->id)->exists()) {
            $this->command?->warn(
                'La versión 1 del formulario de constancia de identidad ya tiene solicitudes; no se modificó su estructura.'
            );

            return;
        }

        $campos = [];
        $ordenSeccion = 1;

        foreach ($this->secciones() as $definicionSeccion) {
            $seccion = FormularioSeccion::query()->firstOrNew([
                'formulario_version_id' => $version->id,
                'clave' => $definicionSeccion['clave'],
            ]);

            if (! $seccion->exists) {
                $seccion->id = (string) Str::uuid();
            }

            $seccion->fill([
                'titulo' => $definicionSeccion['titulo'],
                'descripcion' => $definicionSeccion['descripcion'] ?? null,
                'orden' => $ordenSeccion,
                'activo' => true,
            ])->save();

            $ordenCampo = 1;

            foreach ($definicionSeccion['campos'] as $definicionCampo) {
                $campo = FormularioCampo::query()->firstOrNew([
                    'seccion_id' => $seccion->id,
                    'clave' => $definicionCampo['clave'],
                ]);

                if (! $campo->exists) {
                    $campo->id = (string) Str::uuid();
                }

                $campo->fill([
                    'etiqueta' => $definicionCampo['etiqueta'],
                    'tipo' => $definicionCampo['tipo'],
                    'requerido' => $definicionCampo['requerido'] ?? false,
                    'orden' => $ordenCampo,
                    'activo' => true,
                ])->save();

                $this->sembrarOpciones($campo, $definicionCampo['opciones'] ?? []);
                $campos[$definicionCampo['clave']] = $campo;
                $ordenCampo++;
            }

            $ordenSeccion++;
        }

        $this->sembrarCondiciones($campos);
    }

    private function sembrarOpciones(FormularioCampo $campo, array $opciones): void
    {
        foreach ($opciones as $indice => $etiqueta) {
            $opcion = CampoOpcion::query()->firstOrNew([
                'campo_id' => $campo->id,
                'valor' => (string) ($indice + 1),
            ]);

            if (! $opcion->exists) {
                $opcion->id = (string) Str::uuid();
            }

            $opcion->fill([
                'etiqueta' => $etiqueta,
                'orden' => $indice + 1,
                'activo' => true,
            ])->save();
        }
    }

    private function sembrarCondiciones(array $campos): void
    {
        foreach ($this->condiciones() as $definicion) {
            $destino = $campos[$definicion['destino']] ?? throw new RuntimeException(
                'No existe el campo destino '.$definicion['destino'].' en el formulario de constancia de identidad.'
            );
            $origen = $campos[$definicion['origen']] ?? throw new RuntimeException(
                'No existe el campo origen '.$definicion['origen'].' en el formulario de constancia de identidad.'
            );

            $condicion = CampoCondicion::query()->firstOrNew([
                'campo_destino_id' => $destino->id,
                'campo_origen_id' => $origen->id,
                'operador' => $definicion['operador'],
                'accion' => $definicion['accion'],
            ]);

            if (! $condicion->exists) {
                $condicion->id = (string) Str::uuid();
            }

            $condicion->valor_comparacion = ['valor' => $definicion['valor']];
            $condicion->save();
        }
    }

    private function asignarVersion(Tramite $tramite, FormularioVersion $version): void
    {
        DB::table('tramite_formulario')
            ->where('tramite_id', $tramite->id)
            ->where('activo', true)
            ->where('formulario_version_id', '!=', $version->id)
            ->update([
                'activo' => false,
                'fecha_fin_vigencia' => now(),
                'updated_at' => now(),
            ]);

        $vinculo = DB::table('tramite_formulario')
            ->where('tramite_id', $tramite->id)
            ->where('formulario_version_id', $version->id)
            ->first();

        if ($vinculo) {
            DB::table('tramite_formulario')->where('id', $vinculo->id)->update([
                'activo' => true,
                'fecha_fin_vigencia' => null,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('tramite_formulario')->insert([
            'id' => (string) Str::uuid(),
            'tramite_id' => $tramite->id,
            'formulario_version_id' => $version->id,
            'fecha_inicio_vigencia' => now(),
            'fecha_fin_vigencia' => null,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Claves estables del formulario. El orden del arreglo define el orden visible.
     */
    private function secciones(): array
    {
        return [
            [
                'clave' => 'datos_solicitante',
                'titulo' => 'Datos del solicitante',
                'campos' => [
                    ['clave' => 'fecha_nacimiento', 'etiqueta' => 'Fecha de nacimiento', 'tipo' => 'DATE', 'requerido' => true],
                    ['clave' => 'sexo', 'etiqueta' => 'Sexo', 'tipo' => 'RADIO', 'requerido' => true, 'opciones' => ['Femenino', 'Masculino']],
                    ['clave' => 'mnr', 'etiqueta' => '¿El solicitante es menor de edad?', 'tipo' => 'RADIO', 'requerido' => true, 'opciones' => ['Sí', 'No']],
                ],
            ],
            [
                'clave' => 'domicilio_solicitante',
                'titulo' => 'Domicilio del solicitante',
                'descripcion' => 'El trámite es exclusivo para habitantes del Municipio de Nezahualcóyotl.',
                'campos' => [
                    ['clave' => 'domicilio', 'etiqueta' => 'Domicilio en Nezahualcóyotl', 'tipo' => 'DOMICILIO', 'requerido' => true],
                ],
            ],
            [
                'clave' => 'testigo_1',
                'titulo' => 'Testigo 1',
                'descripcion' => 'Debe ser familiar consanguíneo. Si el solicitante es menor de edad, los dos testigos deben ser su padre y su madre.',
                'campos' => [
                    ['clave' => 't1_nombre', 'etiqueta' => 'Nombre(s) del testigo 1', 'tipo' => 'TEXT', 'requerido' => true],
                    ['clave' => 't1_paterno', 'etiqueta' => 'Apellido paterno del testigo 1', 'tipo' => 'TEXT', 'requerido' => true],
                    ['clave' => 't1_materno', 'etiqueta' => 'Apellido materno del testigo 1', 'tipo' => 'TEXT', 'requerido' => true],
                    ['clave' => 't1_domicilio', 'etiqueta' => 'Domicilio del testigo 1', 'tipo' => 'DOMICILIO_FUERA_NEZA', 'requerido' => true],
                ],
            ],
            [
                'clave' => 'testigo_2',
                'titulo' => 'Testigo 2',
                'descripcion' => 'Debe ser familiar consanguíneo. Si el solicitante es menor de edad, los dos testigos deben ser su padre y su madre.',
                'campos' => [
                    ['clave' => 't2_nombre', 'etiqueta' => 'Nombre(s) del testigo 2', 'tipo' => 'TEXT', 'requerido' => true],
                    ['clave' => 't2_paterno', 'etiqueta' => 'Apellido paterno del testigo 2', 'tipo' => 'TEXT', 'requerido' => true],
                    ['clave' => 't2_materno', 'etiqueta' => 'Apellido materno del testigo 2', 'tipo' => 'TEXT', 'requerido' => true],
                    ['clave' => 't2_domicilio', 'etiqueta' => 'Domicilio del testigo 2', 'tipo' => 'DOMICILIO_FUERA_NEZA', 'requerido' => true],
                ],
            ],
            [
                'clave' => 'documentos_solicitante',
                'titulo' => 'Documentos del solicitante',
                'descripcion' => 'Adjunta los documentos en PDF, JPG, PNG, DOC o DOCX. Tamaño máximo por archivo: 10 MB.',
                'campos' => [
                    ['clave' => 'acta_nacimiento', 'etiqueta' => 'Acta de nacimiento actualizada', 'tipo' => 'FILE', 'requerido' => true],
                    ['clave' => 'acta_extemporanea', 'etiqueta' => '¿El acta de nacimiento es de registro extemporáneo?', 'tipo' => 'RADIO', 'requerido' => true, 'opciones' => ['Sí', 'No']],
                    ['clave' => 'constancia_no_registro_neza', 'etiqueta' => 'Constancia de no registro del Registro Civil de Nezahualcóyotl', 'tipo' => 'FILE', 'requerido' => false],
                    ['clave' => 'constancia_no_registro_chimalhuacan', 'etiqueta' => 'Constancia de no registro del Registro Civil de Chimalhuacán', 'tipo' => 'FILE', 'requerido' => false],
                    ['clave' => 'constancia_no_registro_cdmx', 'etiqueta' => 'Constancia de no registro del Registro Civil de la Ciudad de México', 'tipo' => 'FILE', 'requerido' => false],
                    ['clave' => 'comprobante_domicilio', 'etiqueta' => 'Comprobante de domicilio (no mayor a 3 meses)', 'tipo' => 'FILE', 'requerido' => true],
                    ['clave' => 'fotografia', 'etiqueta' => 'Fotografía tamaño infantil, fondo blanco, papel mate', 'tipo' => 'FILE', 'requerido' => true],
                ],
            ],
            [
                'clave' => 'documentos_testigos',
                'titulo' => 'Documentos de los testigos',
                'campos' => [
                    ['clave' => 't1_credencial', 'etiqueta' => 'Credencial para votar del testigo 1 (vigente)', 'tipo' => 'FILE', 'requerido' => true],
                    ['clave' => 't1_comprobante', 'etiqueta' => 'Comprobante de domicilio del testigo 1 (no mayor a 3 meses)', 'tipo' => 'FILE', 'requerido' => true],
                    ['clave' => 't2_credencial', 'etiqueta' => 'Credencial para votar del testigo 2 (vigente)', 'tipo' => 'FILE', 'requerido' => true],
                    ['clave' => 't2_comprobante', 'etiqueta' => 'Comprobante de domicilio del testigo 2 (no mayor a 3 meses)', 'tipo' => 'FILE', 'requerido' => true],
                ],
            ],
            [
                'clave' => 'casos_especiales',
                'titulo' => 'Casos especiales',
                'campos' => [
                    ['clave' => 'familiar_hospitalizado', 'etiqueta' => '¿La constancia es para un familiar hospitalizado?', 'tipo' => 'RADIO', 'requerido' => true, 'opciones' => ['Sí', 'No']],
                    ['clave' => 'constancia_hospitalizacion', 'etiqueta' => 'Constancia de hospitalización sellada y firmada por Trabajo Social', 'tipo' => 'FILE', 'requerido' => false],
                    ['clave' => 'padre_fallecido', 'etiqueta' => '¿Alguno de los padres del solicitante falleció?', 'tipo' => 'RADIO', 'requerido' => false, 'opciones' => ['Sí', 'No']],
                    ['clave' => 'acta_defuncion', 'etiqueta' => 'Acta de defunción del padre o de la madre', 'tipo' => 'FILE', 'requerido' => false],
                ],
            ],
            [
                'clave' => 'declaracion',
                'titulo' => 'Declaración',
                'campos' => [
                    ['clave' => 'acepto_declaracion', 'etiqueta' => 'Acepto que la información proporcionada es verídica y que el trámite es personal.', 'tipo' => 'CHECKBOX', 'requerido' => true],
                ],
            ],
        ];
    }

    /**
     * Las condiciones se comparan contra la etiqueta de la opción (por eso usa "Sí"),
     * tanto en la validación del servidor como en JavaScript.
     */
    private function condiciones(): array
    {
        return [
            ['destino' => 'constancia_hospitalizacion', 'origen' => 'familiar_hospitalizado', 'operador' => 'EQUALS', 'valor' => 'Sí', 'accion' => 'SHOW'],
            ['destino' => 'constancia_hospitalizacion', 'origen' => 'familiar_hospitalizado', 'operador' => 'EQUALS', 'valor' => 'Sí', 'accion' => 'REQUIRE'],
            ['destino' => 'padre_fallecido', 'origen' => 'mnr', 'operador' => 'EQUALS', 'valor' => 'Sí', 'accion' => 'SHOW'],
            ['destino' => 'padre_fallecido', 'origen' => 'mnr', 'operador' => 'EQUALS', 'valor' => 'Sí', 'accion' => 'REQUIRE'],
            ['destino' => 'acta_defuncion', 'origen' => 'padre_fallecido', 'operador' => 'EQUALS', 'valor' => 'Sí', 'accion' => 'SHOW'],
            ['destino' => 'acta_defuncion', 'origen' => 'padre_fallecido', 'operador' => 'EQUALS', 'valor' => 'Sí', 'accion' => 'REQUIRE'],
            ['destino' => 'constancia_no_registro_neza', 'origen' => 'acta_extemporanea', 'operador' => 'EQUALS', 'valor' => 'Sí', 'accion' => 'SHOW'],
            ['destino' => 'constancia_no_registro_neza', 'origen' => 'acta_extemporanea', 'operador' => 'EQUALS', 'valor' => 'Sí', 'accion' => 'REQUIRE'],
            ['destino' => 'constancia_no_registro_chimalhuacan', 'origen' => 'acta_extemporanea', 'operador' => 'EQUALS', 'valor' => 'Sí', 'accion' => 'SHOW'],
            ['destino' => 'constancia_no_registro_chimalhuacan', 'origen' => 'acta_extemporanea', 'operador' => 'EQUALS', 'valor' => 'Sí', 'accion' => 'REQUIRE'],
            ['destino' => 'constancia_no_registro_cdmx', 'origen' => 'acta_extemporanea', 'operador' => 'EQUALS', 'valor' => 'Sí', 'accion' => 'SHOW'],
            ['destino' => 'constancia_no_registro_cdmx', 'origen' => 'acta_extemporanea', 'operador' => 'EQUALS', 'valor' => 'Sí', 'accion' => 'REQUIRE'],
        ];
    }
}
