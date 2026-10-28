<?php

namespace Tests\Feature;

use App\Models\AreaOperativa;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AreaOperativaAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('areas_operativas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('accesos_departamento_id', 100)->unique();
            $table->string('nombre_snapshot', 200);
            $table->string('siglas_snapshot', 50)->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('areas_operativas');

        parent::tearDown();
    }

    public function test_colaborador_is_redirected_from_general_dashboard_to_its_area(): void
    {
        $this->withSession(['accesos' => [
            'id' => 'colaborador-1',
            'nombre' => 'Colaborador',
            'roles' => ['colaborador'],
            'departamento_accesos_id' => '10',
            'departamento' => 'Desarrollo Urbano',
        ]]);

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('areas.resumen', '10'));
    }

    public function test_colaborador_cannot_access_another_operational_area(): void
    {
        AreaOperativa::create([
            'accesos_departamento_id' => '20',
            'nombre_snapshot' => 'Secretaría del Ayuntamiento',
            'activa' => true,
        ]);

        $this->withSession(['accesos' => [
            'id' => 'colaborador-1',
            'nombre' => 'Colaborador',
            'roles' => ['colaborador'],
            'departamento_accesos_id' => '10',
            'departamento' => 'Desarrollo Urbano',
        ]]);

        $this->get(route('areas.resumen', '20'))->assertForbidden();
    }

    public function test_emergency_logout_page_is_available_to_an_authenticated_user(): void
    {
        $this->withSession(['accesos' => [
            'id' => 'colaborador-1',
            'roles' => ['colaborador'],
            'departamento_accesos_id' => '10',
        ]]);

        $this->get(route('accesos.logout.confirm'))
            ->assertOk()
            ->assertSee('Cerrar sesión y volver al acceso');
    }
}
