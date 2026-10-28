<?php

namespace Tests\Feature;

use App\Models\Formulario;
use App\Models\Tramite;
use Database\Seeders\ConstanciaResidenciaSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConstanciaResidenciaSeederTest extends TestCase
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
            $table->string('duracion')->nullable();
            $table->string('costo')->nullable();
            $table->json('modalidades')->nullable();
            $table->json('requisitos')->nullable();
            $table->json('proceso')->nullable();
            $table->json('preguntas_frecuentes')->nullable();
            $table->softDeletes();
        });
    }

    public function test_crea_formulario_y_tramite_en_borrador_con_testigos_condicionales(): void
    {
        $this->seed(ConstanciaResidenciaSeeder::class);

        $tramite = Tramite::where('clave', 'CONST_RESIDENCIA')->firstOrFail();
        $formulario = Formulario::where('nombre', 'Constancia de residencia 2026')->firstOrFail();
        $version = $formulario->versiones()->with('estado')->firstOrFail();
        $this->assertSame('BORRADOR', $tramite->estado->clave);
        $this->assertSame('BORRADOR', $version->estado->clave);
        $this->assertDatabaseHas('tramite_formulario', ['tramite_id' => $tramite->id, 'formulario_version_id' => $version->id, 'activo' => true]);

        $campos = DB::table('formulario_campos')->join('formulario_secciones', 'formulario_secciones.id', '=', 'formulario_campos.seccion_id')
            ->where('formulario_secciones.formulario_version_id', $version->id)->select('formulario_campos.*')->get()->keyBy('clave');
        foreach (['calle', 'col', 'municipio', 'cp', 'tramite', 't1_nombre', 't1_paterno', 't1_materno', 't1_calle', 't1_col', 't1_municipio', 't1_cp', 't2_nombre', 't2_paterno', 't2_materno', 't2_calle', 't2_col', 't2_municipio', 't2_cp'] as $clave) {
            $this->assertTrue($campos->has($clave), $clave);
        }
        foreach (['nombres', 'paterno', 'materno'] as $clave) {
            $this->assertFalse($campos->has($clave), $clave);
        }
        foreach (['t1_materno', 't2_materno'] as $clave) {
            $this->assertFalse((bool) $campos[$clave]->requerido);
        }
        $this->assertDatabaseHas('campo_condiciones', ['campo_destino_id' => $campos['t1_nombre']->id, 'campo_origen_id' => $campos['requiere_testigos']->id, 'accion' => 'REQUIRE']);
        $this->assertDatabaseMissing('campo_condiciones', ['campo_destino_id' => $campos['t1_materno']->id, 'accion' => 'REQUIRE']);
    }

    public function test_repetir_el_seeder_no_sobrescribe_el_formulario_ni_el_tramite(): void
    {
        $this->seed(ConstanciaResidenciaSeeder::class);
        $tramite = Tramite::where('clave', 'CONST_RESIDENCIA')->firstOrFail();
        $tramite->update(['nombre' => 'Nombre editado']);
        $formulario = Formulario::where('nombre', 'Constancia de residencia 2026')->firstOrFail();
        $version = $formulario->versiones()->firstOrFail();
        $seccion = $version->secciones()->firstOrFail();
        $seccion->update(['titulo' => 'Sección editada']);

        $this->seed(ConstanciaResidenciaSeeder::class);

        $this->assertSame('Nombre editado', $tramite->fresh()->nombre);
        $this->assertSame('Sección editada', $seccion->fresh()->titulo);
        $this->assertSame(1, $formulario->versiones()->count());
        $this->assertSame(1, DB::table('tramite_formulario')->where('tramite_id', $tramite->id)->count());
    }
}
