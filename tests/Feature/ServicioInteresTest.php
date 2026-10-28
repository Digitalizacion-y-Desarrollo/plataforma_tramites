<?php

namespace Tests\Feature;

use App\Models\ServicioInteres;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServicioInteresTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');

        Schema::create('servicios_interes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('titulo');
            $table->text('descripcion');
            $table->string('enlace');
            $table->string('logo_storage_key');
            $table->timestamps();
        });
        Schema::create('auditorias', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('actor_accesos_id')->nullable();
            $table->string('actor_nombre_snapshot')->nullable();
            $table->string('actor_departamento_snapshot')->nullable();
            $table->string('accion');
            $table->string('entidad');
            $table->uuid('entidad_id')->nullable();
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->string('ip')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function test_service_routes_require_an_accesos_identity(): void
    {
        $this->get(route('servicios-interes.index'))->assertRedirect(route('accesos.login'));
        $this->get(route('servicios-interes.create'))->assertRedirect(route('accesos.login'));
        $this->post(route('servicios-interes.store'), $this->datos())->assertRedirect(route('accesos.login'));
    }

    public function test_service_can_be_created_shown_updated_and_removed(): void
    {
        $this->withSession(['accesos' => ['id' => 'funcionario-1', 'nombre' => 'Prueba', 'roles' => ['superadmin']]]);

        $this->post(route('servicios-interes.store'), $this->datos())
            ->assertRedirect(route('servicios-interes.index'));

        $servicio = ServicioInteres::firstOrFail();
        Storage::disk('public')->assertExists($servicio->logo_storage_key);
        $this->assertDatabaseHas('auditorias', ['accion' => 'SERVICIO_INTERES_CREADO', 'actor_accesos_id' => 'funcionario-1']);
        $this->get(route('servicios-interes.index'))->assertOk()->assertSee('Pago en línea');
        $this->get(route('portal.publico'))->assertOk()->assertSee('Pago en línea')->assertSee('https://ejemplo.gob.mx/pagos');

        $logoAnterior = $servicio->logo_storage_key;
        $this->put(route('servicios-interes.update', $servicio), $this->datos([
            'titulo' => 'Nuevo nombre', 'logo' => UploadedFile::fake()->image('nuevo.png'),
        ]))->assertRedirect(route('servicios-interes.index'));

        $servicio->refresh();
        Storage::disk('public')->assertMissing($logoAnterior);
        Storage::disk('public')->assertExists($servicio->logo_storage_key);
        $this->get(route('portal.publico'))->assertOk()->assertSee('Nuevo nombre')->assertDontSee('Pago en línea');

        $logoNuevo = $servicio->logo_storage_key;
        $this->delete(route('servicios-interes.destroy', $servicio))->assertRedirect(route('servicios-interes.index'));
        $this->assertDatabaseMissing('servicios_interes', ['id' => $servicio->id]);
        Storage::disk('public')->assertMissing($logoNuevo);
        $this->assertDatabaseHas('auditorias', ['accion' => 'SERVICIO_INTERES_ELIMINADO', 'entidad_id' => $servicio->id]);
    }

    public function test_rejects_unsafe_link_and_non_image_logo(): void
    {
        $this->withSession(['accesos' => ['id' => 'funcionario-1', 'roles' => ['superadmin']]]);
        $this->post(route('servicios-interes.store'), $this->datos([
            'enlace' => 'javascript:alert(1)',
            'logo' => UploadedFile::fake()->create('archivo.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasErrors(['enlace', 'logo']);
        $this->assertDatabaseCount('servicios_interes', 0);
    }

    public function test_logo_url_uses_the_current_request_host(): void
    {
        $servicio = ServicioInteres::create([
            'titulo' => 'Pago en línea',
            'descripcion' => 'Servicios municipales',
            'enlace' => 'https://ejemplo.gob.mx/pagos',
            'logo_storage_key' => 'servicios-interes/logo.png',
        ]);

        $this->get('http://127.0.0.1:8000/')
            ->assertOk()
            ->assertSee('http://127.0.0.1:8000/storage/'.$servicio->logo_storage_key);
    }

    private function datos(array $cambios = []): array
    {
        return array_replace([
            'titulo' => 'Pago en línea',
            'descripcion' => 'Consulta y paga tus servicios municipales.',
            'enlace' => 'https://ejemplo.gob.mx/pagos',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ], $cambios);
    }
}
