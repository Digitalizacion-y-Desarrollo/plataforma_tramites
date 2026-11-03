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

class LicenciaUsoSueloSeeder extends Seeder
{
    private const TRAMITE_CLAVE = 'LIC_USO_SUELO';

    private const FORMULARIO_NOMBRE = 'Licencia de Uso de Suelo';

    private const DEPARTAMENTO_ID = '1';

    private const DEPARTAMENTO_NOMBRE = 'DIRECCIÓN DE DESARROLLO URBANO';

    public function run(): void
    {
        $this->call([FormularioEstadoSeeder::class, TramiteEstadoSeeder::class]);

        DB::transaction(function (): void {
            $tramiteExiste = Tramite::withTrashed()->where('clave', $this->tramiteClave())->exists();
            $formularioExiste = Formulario::withTrashed()->where('nombre', $this->formularioNombre())->where('departamento_accesos_id', self::DEPARTAMENTO_ID)->exists();
            if ($tramiteExiste && $formularioExiste) {
                return;
            }
            if ($tramiteExiste || $formularioExiste) {
                throw new RuntimeException($this->mensajeExistenciaParcial());
            }

            $borradorFormulario = FormularioEstado::query()->where('clave', 'BORRADOR')->where('activo', true)->firstOrFail();
            $borradorTramite = TramiteEstado::query()->where('clave', 'BORRADOR')->where('activo', true)->firstOrFail();
            $formulario = Formulario::create([
                'id' => (string) Str::uuid(), 'nombre' => $this->formularioNombre(),
                'descripcion' => 'Solicitud de '.$this->tramiteNombre().'.',
                'departamento_accesos_id' => self::DEPARTAMENTO_ID, 'departamento_nombre_snapshot' => self::DEPARTAMENTO_NOMBRE,
            ]);
            $version = $formulario->versiones()->create([
                'id' => (string) Str::uuid(), 'numero_version' => 1, 'formulario_estado_id' => $borradorFormulario->id,
            ]);
            $campos = $this->sembrarCampos($version->id);
            $this->sembrarCondiciones($campos);

            $tramite = Tramite::create([
                'clave' => $this->tramiteClave(), 'nombre' => $this->tramiteNombre(),
                'descripcion' => $this->tramiteNombre().'.', 'departamento_accesos_id' => self::DEPARTAMENTO_ID,
                'departamento_nombre_snapshot' => self::DEPARTAMENTO_NOMBRE, 'modalidades' => ['EN_LINEA'],
                'costo' => '$117.31 MXN', 'costo_pago' => 117.31, 'flujo_pago' => 'PAGO_DIRECTO', 'pago_vigencia_dias' => 30,
                'activo' => true, 'tramite_estado_id' => $borradorTramite->id,
            ]);
            DB::table('tramite_formulario')->insert([
                'id' => (string) Str::uuid(), 'tramite_id' => $tramite->id, 'formulario_version_id' => $version->id,
                'fecha_inicio_vigencia' => now(), 'activo' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    protected function sembrarCampos(string $versionId): array
    {
        $secciones = [
            ['modalidad', 'Modalidad', [
                ['uso_giro', 'Uso y/o giro', 'RADIO'], ['fecha_solicitud', 'Fecha de solicitud', 'DATE', ['fecha_actual_bloqueada' => true]], ['giro_solicitado', 'Giro solicitado'], ['protesta_verdad', 'Declaración bajo protesta de decir verdad', 'CHECKBOX', ['texto_legal' => 'Declaro bajo protesta decir la verdad que la información y documentación proporcionada es verídica, por lo que en el caso de existir falsedad en ella, tengo pleno conocimiento que se aplicarán sanciones administrativas y penales establecidas en los ordenamientos respectivos para quienes se conducen con falsedad ante la autoridad competente, en los términos del artículo 243 del Código Penal Federal; artículo 173 del Código Penal del Estado de México, en correlación con el artículo 5.10 fracción XX del Código Administrativo del Estado de México.']],
            ]],
            ['interesado', 'Datos del interesado', [
                ['tipo_persona', 'Tipo de persona', 'RADIO'], ['fisica_constancia_fiscal', 'Constancia de Situación Fiscal', 'FILE'], ['identificacion_fisica', 'Identificación oficial', 'RADIO'], ['ine', 'INE', 'FILE'], ['pasaporte', 'Pasaporte', 'FILE'], ['soy_extranjero', 'Soy extranjero', 'CHECKBOX'], ['situacion_migratoria', 'Documento vigente que acredite su situación migratoria y estancia legal en el país', 'FILE'],
                ['razon_social', 'Denominación o razón social'], ['moral_constancia_fiscal', 'Constancia de Situación Fiscal', 'FILE'], ['domicilio_moral', 'Domicilio', 'DOMICILIO'], ['poliza_acta', 'Número de póliza o acta administrativa'], ['estado_moral', 'Entidad Federativa', 'ESTADO'],
                ['representante_legal', 'Nombre de la persona apoderada y/o representante legal'], ['documento_representante', 'Documento de identificación de la persona apoderada y/o representante legal', 'RADIO'], ['archivo_representante', 'Documento de identificación seleccionado', 'FILE'],
            ]],
            ['propiedad', 'Documento que acredite la propiedad o legal posesión', [
                ['escritura_publica', 'Número de la escritura pública'], ['numero_notaria', 'Número de la notaría', 'NUMBER'], ['fecha_escritura', 'Fecha', 'DATE'], ['estado_escritura', 'Entidad Federativa', 'ESTADO'], ['titular_notaria', 'Nombre de la persona titular de la notaría'], ['folio_registro', 'Folio de inscripción en el Registro Público de la Propiedad y de Comercio'], ['tipo_posesion', 'Documento de posesión', 'RADIO'], ['otro_documento', 'Indica qué documento presenta'], ['archivo_propiedad', 'Documento', 'FILE'],
            ]],
            ['ubicacion_predio', 'Datos de ubicación del predio', [
                ['cuenta_catastral', 'Cuenta catastral (conforme a boleta predial)'], ['estado_predio', 'Entidad Federativa', 'ESTADO'], ['domicilio_predio', 'Domicilio del predio', 'DOMICILIO'],
            ]],
            ['croquis', 'Croquis de localización', [
                ['mapa_predio', 'Ubicación del predio en el mapa', 'MAPA', ['domicilio_origen_clave' => 'domicilio_predio']],
            ]],
        ];
        $campos = [];
        foreach ($secciones as $indice => [$clave, $titulo, $definiciones]) {
            $seccion = FormularioSeccion::create(['id' => (string) Str::uuid(), 'formulario_version_id' => $versionId, 'clave' => $clave, 'titulo' => $titulo, 'orden' => $indice + 1, 'activo' => true]);
            foreach ($definiciones as $orden => $definicion) {
                [$claveCampo, $etiqueta, $tipo, $configuracion] = array_pad($definicion, 4, null);
                $campos[$claveCampo] = FormularioCampo::create(['id' => (string) Str::uuid(), 'seccion_id' => $seccion->id, 'clave' => $claveCampo, 'etiqueta' => $etiqueta, 'tipo' => $tipo ?? 'TEXT', 'requerido' => true, 'orden' => $orden + 1, 'configuracion' => $configuracion, 'activo' => true]);
            }
        }
        $campos['mapa_predio']->update(['configuracion' => ['domicilio_origen_id' => $campos['domicilio_predio']->id]]);
        $this->opciones($campos['uso_giro'], $this->opcionesModalidad());
        $this->opciones($campos['tipo_persona'], ['Física', 'Moral']);
        $this->opciones($campos['identificacion_fisica'], ['INE', 'Pasaporte']);
        $this->opciones($campos['documento_representante'], ['INE', 'Documento que acredite su personalidad jurídica', 'Carta poder (firmada por dos testigos)', 'Instrumento notarial', 'Pasaporte']);
        $this->opciones($campos['tipo_posesion'], ['Contrato de arrendamiento', 'Comodato', 'Otro']);

        return $campos;
    }

    protected function opciones(FormularioCampo $campo, array $opciones): void
    {
        foreach ($opciones as $orden => $etiqueta) {
            CampoOpcion::create(['id' => (string) Str::uuid(), 'campo_id' => $campo->id, 'valor' => (string) ($orden + 1), 'etiqueta' => $etiqueta, 'orden' => $orden + 1, 'activo' => true]);
        }
    }

    protected function sembrarCondiciones(array $c): void
    {
        $mostrar = function (string $destino, string $origen, string $valor, string $accion = 'SHOW') use ($c): void {
            CampoCondicion::create(['id' => (string) Str::uuid(), 'campo_destino_id' => $c[$destino]->id, 'campo_origen_id' => $c[$origen]->id, 'operador' => 'EQUALS', 'valor_comparacion' => ['valor' => $valor], 'accion' => $accion]);
        };
        $marcado = function (string $destino, string $origen, string $accion = 'SHOW') use ($c): void {
            CampoCondicion::create(['id' => (string) Str::uuid(), 'campo_destino_id' => $c[$destino]->id, 'campo_origen_id' => $c[$origen]->id, 'operador' => 'IS_NOT_EMPTY', 'valor_comparacion' => null, 'accion' => $accion]);
        };
        foreach (['fisica_constancia_fiscal', 'identificacion_fisica', 'ine', 'pasaporte', 'soy_extranjero', 'situacion_migratoria'] as $campo) {
            $mostrar($campo, 'tipo_persona', 'Física');
        }
        foreach (['razon_social', 'moral_constancia_fiscal', 'domicilio_moral', 'poliza_acta', 'estado_moral'] as $campo) {
            $mostrar($campo, 'tipo_persona', 'Moral');
        }
        $mostrar('ine', 'identificacion_fisica', 'INE');
        $mostrar('pasaporte', 'identificacion_fisica', 'Pasaporte');
        foreach (['identificacion_fisica', 'ine', 'pasaporte'] as $campo) {
            $marcado($campo, 'soy_extranjero', 'HIDE');
        }
        $marcado('situacion_migratoria', 'soy_extranjero');
        $mostrar('otro_documento', 'tipo_posesion', 'Otro');
    }

    protected function tramiteClave(): string
    {
        return self::TRAMITE_CLAVE;
    }

    protected function mensajeExistenciaParcial(): string
    {
        return 'La Licencia de Uso de Suelo existe parcialmente; revisa los registros antes de ejecutar el seeder.';
    }

    protected function formularioNombre(): string
    {
        return self::FORMULARIO_NOMBRE;
    }

    protected function tramiteNombre(): string
    {
        return self::FORMULARIO_NOMBRE;
    }

    protected function opcionesModalidad(): array
    {
        return ['Habitacional', 'Comercial', 'Industrial', 'Nuevo', 'Refrendo'];
    }
}
