<?php

namespace Tests\Feature;

use App\Models\Tramite;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicoTramitesDependenciasTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->crearEsquemaDeTramites();

        $this->crearTramite('Constancia de terminación de obra', '1', 'DIRECCIÓN DE DESARROLLO URBANO');
        $this->crearTramite('Constancia de suspensión de obra', '1', 'DIRECCIÓN DE DESARROLLO URBANO');
        $this->crearTramite('Constancia de regularización', '30', 'SECRETARÍA DEL H. AYUNTAMIENTO');
    }

    public function test_catalogo_publico_agrupa_los_tramites_por_dependencia(): void
    {
        $this->get(route('portal.tramites.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'Dirección de Desarrollo Urbano',
                'Constancia de suspensión de obra',
                'Constancia de terminación de obra',
                'Secretaría del H. Ayuntamiento',
                'Constancia de regularización',
            ])
            ->assertSee('2 trámites', false)
            ->assertSee('1 trámite', false)
            ->assertSee('Todas las dependencias');
    }

    public function test_catalogo_publico_filtra_los_tramites_por_dependencia(): void
    {
        $this->get(route('portal.tramites.index', ['dependencia' => '30']))
            ->assertOk()
            ->assertSee('Constancia de regularización')
            ->assertDontSee('Constancia de terminación de obra')
            ->assertDontSee('Constancia de suspensión de obra')
            ->assertSee('Filtros activos')
            ->assertSee('Dependencia: Secretaría del H. Ayuntamiento')
            ->assertSee('Se encontraron');
    }

    public function test_busqueda_incluye_el_nombre_de_la_dependencia(): void
    {
        $this->get(route('portal.tramites.index', ['q' => 'desarrollo urbano']))
            ->assertOk()
            ->assertSee('Constancia de terminación de obra')
            ->assertSee('Constancia de suspensión de obra')
            ->assertDontSee('Constancia de regularización');
    }

    public function test_dependencia_desconocida_no_filtra_el_catalogo(): void
    {
        $this->get(route('portal.tramites.index', ['dependencia' => '999']))
            ->assertOk()
            ->assertSee('Constancia de regularización')
            ->assertSee('Constancia de terminación de obra')
            ->assertSee('Constancia de suspensión de obra');
    }

    private function crearTramite(string $nombre, string $departamentoId, string $departamentoNombre): void
    {
        Tramite::query()->create([
            'clave' => 'TRM-'.str()->random(8),
            'nombre' => $nombre,
            'descripcion' => 'Descripción de '.$nombre.'.',
            'departamento_accesos_id' => $departamentoId,
            'departamento_nombre_snapshot' => $departamentoNombre,
            'modalidades' => ['EN_LINEA'],
            'activo' => true,
        ]);
    }

    private function crearEsquemaDeTramites(): void
    {
        Schema::create('tramite_estados', function (Blueprint $table): void {
            $table->id();
            $table->string('clave');
            $table->string('nombre');
            $table->timestamps();
        });
        Schema::create('tramites', function (Blueprint $table): void {
            $table->id();
            $table->string('clave')->nullable();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('departamento_accesos_id')->nullable();
            $table->string('departamento_nombre_snapshot')->nullable();
            $table->string('duracion')->nullable();
            $table->string('costo')->nullable();
            $table->string('flujo_pago')->default('SIN_PAGO');
            $table->decimal('costo_pago', 12, 2)->nullable();
            $table->unsignedSmallInteger('pago_vigencia_dias')->nullable();
            $table->string('tiempo_estimado')->nullable();
            $table->json('modalidades')->nullable();
            $table->json('requisitos')->nullable();
            $table->json('proceso')->nullable();
            $table->json('preguntas_frecuentes')->nullable();
            $table->unsignedInteger('descarga_expira_dias')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('tramite_estado_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
}
