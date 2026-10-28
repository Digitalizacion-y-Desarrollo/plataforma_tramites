<?php

namespace Tests\Feature;

use App\Integrations\AccesosClient;
use App\Models\Auditoria;
use App\Models\EstadoSolicitud;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        Schema::create('auditorias', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('actor_accesos_id', 100)->nullable();
            $table->string('actor_nombre_snapshot', 200)->nullable();
            $table->string('actor_departamento_snapshot', 200)->nullable();
            $table->string('accion', 100);
            $table->string('entidad', 100);
            $table->string('entidad_id', 36)->nullable();
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at');
        });
    }

    public function test_registra_actor_nombre_y_departamento_desde_la_sesion(): void
    {
        $sesion = $this->app['session']->driver();
        $sesion->start();
        $sesion->put('accesos', [
            'id' => 'funcionario-7',
            'nombre' => 'Ana López',
            'departamento' => 'Desarrollo Urbano',
        ]);

        $request = Request::create('/prueba', 'GET', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $request->setLaravelSession($sesion);

        Auditoria::registrar($request, 'PRUEBA_ACCION', 'PRUEBA', null, ['antes' => 1], ['despues' => 2]);

        $auditoria = Auditoria::firstOrFail();
        $this->assertSame('funcionario-7', $auditoria->actor_accesos_id);
        $this->assertSame('Ana López', $auditoria->actor_nombre_snapshot);
        $this->assertSame('Desarrollo Urbano', $auditoria->actor_departamento_snapshot);
        $this->assertSame(['antes' => 1], $auditoria->datos_anteriores);
        $this->assertSame(['despues' => 2], $auditoria->datos_nuevos);
    }

    public function test_catalogo_de_estados_registra_auditoria_en_cada_operacion(): void
    {
        Schema::create('solicitud_estados', function (Blueprint $table): void {
            $table->id();
            $table->string('clave', 50)->unique();
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('es_final')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('solicitudes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('solicitud_estado_id')->nullable();
        });
        Schema::create('solicitud_historial_estados', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('estado_anterior_id')->nullable();
            $table->unsignedBigInteger('estado_nuevo_id')->nullable();
        });

        $this->withSession(['accesos' => [
            'id' => 'superuser-1',
            'nombre' => 'Revisor',
            'departamento' => 'Desarrollo Urbano',
            'roles' => ['superuser'],
        ]]);

        $this->post(route('catalogo-estados.store'), [
            'nombre' => 'Estado de prueba',
            'descripcion' => 'Descripción',
            'orden' => 1,
            'activo' => 1,
        ])->assertRedirect(route('catalogo-estados.index'));

        $estado = EstadoSolicitud::firstOrFail();
        $this->assertDatabaseHas('auditorias', [
            'accion' => 'ESTADO_SOLICITUD_CREADO',
            'entidad' => 'ESTADO_SOLICITUD',
            'entidad_id' => (string) $estado->id,
            'actor_accesos_id' => 'superuser-1',
            'actor_departamento_snapshot' => 'Desarrollo Urbano',
        ]);

        $this->put(route('catalogo-estados.update', $estado), [
            'nombre' => 'Estado modificado',
            'orden' => 2,
            'activo' => 1,
        ])->assertRedirect(route('catalogo-estados.index'));

        $this->assertDatabaseHas('auditorias', [
            'accion' => 'ESTADO_SOLICITUD_ACTUALIZADO',
            'entidad_id' => (string) $estado->id,
            'actor_accesos_id' => 'superuser-1',
        ]);

        $this->patch(route('catalogo-estados.estado', $estado))->assertRedirect(route('catalogo-estados.index'));
        $this->assertDatabaseHas('auditorias', [
            'accion' => 'ESTADO_SOLICITUD_DESACTIVADO',
            'entidad_id' => (string) $estado->id,
        ]);

        $this->delete(route('catalogo-estados.destroy', $estado))->assertRedirect(route('catalogo-estados.index'));
        $this->assertDatabaseHas('auditorias', [
            'accion' => 'ESTADO_SOLICITUD_ELIMINADO',
            'entidad_id' => (string) $estado->id,
        ]);
    }

    public function test_pantalla_de_auditoria_requiere_rol_y_permite_filtrar(): void
    {
        Auditoria::create([
            'id' => (string) Str::uuid(),
            'actor_accesos_id' => 'admin-1',
            'actor_nombre_snapshot' => 'Administrador',
            'accion' => 'FORMULARIO_CREADO',
            'entidad' => 'FORMULARIO',
            'entidad_id' => (string) Str::uuid(),
            'created_at' => now(),
        ]);
        Auditoria::create([
            'id' => (string) Str::uuid(),
            'actor_accesos_id' => 'admin-2',
            'actor_nombre_snapshot' => 'Revisor',
            'accion' => 'TRAMITE_CREADO',
            'entidad' => 'TRAMITE',
            'entidad_id' => (string) Str::uuid(),
            'created_at' => now(),
        ]);

        $this->withSession(['accesos' => ['id' => 'colaborador-1', 'nombre' => 'Colaborador', 'roles' => ['colaborador']]]);
        $this->get(route('auditoria.index'))->assertForbidden();

        $this->withSession(['accesos' => ['id' => 'superadmin-1', 'nombre' => 'Superadmin', 'roles' => ['superadmin']]]);
        $this->get(route('auditoria.index'))
            ->assertOk()
            ->assertSee('FORMULARIO_CREADO')
            ->assertSee('TRAMITE_CREADO')
            ->assertSee('Administrador');

        $this->get(route('auditoria.index', ['accion' => 'FORMULARIO_CREADO']))
            ->assertOk()
            ->assertSee('Administrador')
            ->assertDontSee('Revisor');
    }

    public function test_inicio_y_cierre_de_sesion_se_auditan(): void
    {
        $this->mock(AccesosClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('iniciarSesion')->once()->andReturn(['access_token' => 'token-prueba']);
            $mock->shouldReceive('perfil')->once()->andReturn([
                'user' => [
                    'id' => 42,
                    'name' => 'Ana López',
                    'departamento' => ['departamento_padre' => ['id' => 9, 'nombre' => 'Desarrollo Urbano']],
                ],
                'roles' => ['superuser'],
            ]);
            $mock->shouldReceive('cerrarSesion')->once()->andReturnNull();
        });

        $this->post(route('accesos.login.store'), [
            'email' => 'ana@example.test',
            'password' => 'secreto',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('auditorias', [
            'accion' => 'ACCESO_INICIADO',
            'actor_accesos_id' => '42',
            'actor_nombre_snapshot' => 'Ana López',
            'actor_departamento_snapshot' => 'Desarrollo Urbano',
        ]);

        $this->post(route('accesos.logout'))->assertRedirect(route('accesos.login'));

        $this->assertDatabaseHas('auditorias', [
            'accion' => 'ACCESO_CERRADO',
            'actor_accesos_id' => '42',
            'actor_nombre_snapshot' => 'Ana López',
        ]);
    }
}
