<?php

namespace Tests\Feature;

use App\Models\Formulario;
use App\Models\Tramite;
use Database\Seeders\LicenciaUnicaConstruccionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LicenciaUnicaConstruccionSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['2026_09_29_000002', '2026_09_29_000005', '2026_09_29_000006', '2026_09_29_000007', '2026_09_29_000008', '2026_09_29_000009', '2026_09_29_000010', '2026_09_29_000011', '2026_09_29_000012', '2026_10_02_000029'] as $prefijo) {
            $archivo = glob(database_path('migrations/'.$prefijo.'_*.php'))[0];
            (require $archivo)->up();
        }
        Schema::table('formulario_estados', fn (Blueprint $table) => $table->softDeletes());
        Schema::table('formularios', function (Blueprint $table): void {
            $table->string('departamento_accesos_id')->nullable();
            $table->string('departamento_nombre_snapshot')->nullable();
            $table->softDeletes();
        });
        Schema::table('tramites', function (Blueprint $table): void {
            $table->string('departamento_accesos_id')->nullable();
            $table->string('departamento_nombre_snapshot')->nullable();
            $table->unsignedBigInteger('tramite_estado_id')->nullable();
            $table->decimal('costo_pago', 10, 2)->nullable();
            $table->string('flujo_pago', 30)->default('SIN_PAGO');
            $table->unsignedSmallInteger('pago_vigencia_dias')->nullable();
            $table->string('duracion')->nullable();
            $table->string('costo')->nullable();
            $table->json('modalidades')->nullable();
            $table->json('requisitos')->nullable();
            $table->json('proceso')->nullable();
            $table->json('preguntas_frecuentes')->nullable();
            $table->softDeletes();
        });
    }

    public function test_crea_el_tramite_y_formulario_en_borrador_con_el_flujo_de_pago_solicitado(): void
    {
        $this->seed(LicenciaUnicaConstruccionSeeder::class);

        $tramite = Tramite::where('clave', 'LIC_UNICA_CONSTRUCCION')->firstOrFail();
        $formulario = Formulario::where('nombre', 'Licencia Única de Construcción')->firstOrFail();
        $version = $formulario->versiones()->with('estado')->firstOrFail();

        $this->assertSame('BORRADOR', $tramite->estado->clave);
        $this->assertSame('BORRADOR', $version->estado->clave);
        $this->assertSame('DIRECCIÓN DE DESARROLLO URBANO', $tramite->departamento_nombre_snapshot);
        $this->assertSame('1', $tramite->departamento_accesos_id);
        $this->assertSame('PAGO_DIRECTO', $tramite->flujo_pago);
        $this->assertSame('$117.31 MXN', $tramite->costo);
        $this->assertSame('117.31', $tramite->costo_pago);
        $this->assertSame(30, $tramite->pago_vigencia_dias);
        $this->assertSame(1, $version->numero_version);
        $this->assertDatabaseHas('tramite_formulario', [
            'tramite_id' => $tramite->id,
            'formulario_version_id' => $version->id,
            'activo' => true,
        ]);
    }

    public function test_conserva_las_secciones_y_el_mapa_vinculado_al_domicilio_del_predio(): void
    {
        $this->seed(LicenciaUnicaConstruccionSeeder::class);

        $version = Formulario::where('nombre', 'Licencia Única de Construcción')->firstOrFail()->versiones()->firstOrFail();
        $this->assertSame(
            ['Modalidad', 'Datos del interesado', 'Documento que acredite la propiedad o legal posesión', 'Datos de ubicación del predio', 'Croquis de localización'],
            $version->secciones()->orderBy('orden')->pluck('titulo')->all(),
        );

        $campos = DB::table('formulario_campos')
            ->join('formulario_secciones', 'formulario_secciones.id', '=', 'formulario_campos.seccion_id')
            ->where('formulario_secciones.formulario_version_id', $version->id)
            ->select('formulario_campos.*')
            ->get()
            ->keyBy('clave');

        $this->assertSame($campos['domicilio_predio']->id, json_decode($campos['mapa_predio']->configuracion, true)['domicilio_origen_id']);
        $this->assertTrue((bool) json_decode($campos['fecha_solicitud']->configuracion, true)['fecha_actual_bloqueada']);
        $this->assertSame('MAPA', $campos['mapa_predio']->tipo);
        $this->assertSame('DOMICILIO', $campos['domicilio_predio']->tipo);
    }

    public function test_reemplaza_las_opciones_de_modalidad_y_agrega_el_campo_condicional_otro(): void
    {
        $this->seed(LicenciaUnicaConstruccionSeeder::class);

        $version = Formulario::where('nombre', 'Licencia Única de Construcción')->firstOrFail()->versiones()->firstOrFail();
        $campos = DB::table('formulario_campos')
            ->join('formulario_secciones', 'formulario_secciones.id', '=', 'formulario_campos.seccion_id')
            ->where('formulario_secciones.formulario_version_id', $version->id)
            ->select('formulario_campos.*')
            ->get()
            ->keyBy('clave');

        $opciones = DB::table('campo_opciones')->where('campo_id', $campos['uso_giro']->id)->orderBy('orden')->pluck('etiqueta')->all();
        $this->assertSame([
            'Licencia de construcción.',
            'Licencia de ampliación.',
            'Licencia de remodelación o modificación.',
            'Licencia de demolición, excavación o relleno de tierra.',
            'Barda (más de 10 m por 2.20 m).',
            'Regularización.',
            'Terminación de obra.',
            'Cambio a régimen en condominio.',
            'Prórroga.',
            'Otro',
        ], $opciones);

        $campoOtro = $campos['especifica_otro_tipo_licencia'];
        $this->assertSame('Especifica el otro tipo de licencia', $campoOtro->etiqueta);
        $this->assertSame('TEXT', $campoOtro->tipo);
        $this->assertSame(2, $campoOtro->orden);
        $this->assertSame(3, $campos['fecha_solicitud']->orden);

        $condicion = DB::table('campo_condiciones')
            ->where('campo_destino_id', $campoOtro->id)
            ->where('campo_origen_id', $campos['uso_giro']->id)
            ->firstOrFail();
        $this->assertSame('EQUALS', $condicion->operador);
        $this->assertSame('SHOW', $condicion->accion);
        $this->assertSame('Otro', json_decode($condicion->valor_comparacion, true)['valor']);
    }

    public function test_repetir_el_seeder_no_duplica_ni_sobrescribe_registros(): void
    {
        $this->seed(LicenciaUnicaConstruccionSeeder::class);

        $tramite = Tramite::where('clave', 'LIC_UNICA_CONSTRUCCION')->firstOrFail();
        $formulario = Formulario::where('nombre', 'Licencia Única de Construcción')->firstOrFail();
        $version = $formulario->versiones()->firstOrFail();
        $seccion = $version->secciones()->firstOrFail();
        $camposAntes = DB::table('formulario_campos')->count();

        $tramite->update(['nombre' => 'Nombre editado']);
        $seccion->update(['titulo' => 'Sección editada']);

        $this->seed(LicenciaUnicaConstruccionSeeder::class);

        $this->assertSame('Nombre editado', $tramite->fresh()->nombre);
        $this->assertSame('Sección editada', $seccion->fresh()->titulo);
        $this->assertSame(1, Tramite::where('clave', 'LIC_UNICA_CONSTRUCCION')->count());
        $this->assertSame(1, Formulario::where('nombre', 'Licencia Única de Construcción')->count());
        $this->assertSame(1, $formulario->versiones()->count());
        $this->assertSame(1, DB::table('tramite_formulario')->where('tramite_id', $tramite->id)->count());
        $this->assertSame($camposAntes, DB::table('formulario_campos')->count());
    }
}
