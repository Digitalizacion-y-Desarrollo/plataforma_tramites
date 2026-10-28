<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class SolicitudEdicionRespuestasTest extends TestCase
{
    private string $solicitudId;

    private string $seccionId;

    private string $campoTextoId;

    private string $campoRadioId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        $this->crearSolicitudConFormulario();
        $this->withSession(['accesos' => ['id' => 'superuser-1', 'roles' => ['superuser']]]);
    }

    public function test_actualiza_los_datos_personales_y_registra_auditoria(): void
    {
        $this->put(route('solicitudes.datos-personales.actualizar', $this->solicitudId), [
            'nombres_solicitante' => 'Ana Corregida',
            'apellido_paterno_solicitante' => 'García',
            'apellido_materno_solicitante' => 'López',
            'curp_consulta' => 'GARC900101HMCXXX01',
            'email_contacto' => 'ana@example.test',
            'telefono_contacto' => '5512345678',
        ])->assertRedirect(route('solicitudes.show', $this->solicitudId));

        $this->assertDatabaseHas('solicitudes', [
            'id' => $this->solicitudId,
            'nombres_solicitante' => 'Ana Corregida',
            'nombre_solicitante_snapshot' => 'Ana Corregida García López',
        ]);
        $this->assertDatabaseHas('auditorias', [
            'accion' => 'SOLICITUD_DATOS_PERSONALES_EDITADOS',
            'entidad' => 'SOLICITUD',
            'entidad_id' => $this->solicitudId,
        ]);
    }

    public function test_actualiza_las_respuestas_de_una_seccion_y_registra_auditoria(): void
    {
        $this->put(route('solicitudes.respuestas.actualizar', [$this->solicitudId, $this->seccionId]), [
            'seccion' => $this->seccionId,
            'respuestas' => [
                $this->campoTextoId => 'Testigo Corregido',
                $this->campoRadioId => '2',
            ],
        ])->assertRedirect(route('solicitudes.show', $this->solicitudId));

        $this->assertDatabaseHas('solicitud_respuestas', [
            'solicitud_id' => $this->solicitudId,
            'campo_id' => $this->campoTextoId,
            'valor' => json_encode('Testigo Corregido'),
        ]);
        $this->assertDatabaseHas('solicitud_respuestas', [
            'solicitud_id' => $this->solicitudId,
            'campo_id' => $this->campoRadioId,
            'valor' => json_encode('2'),
        ]);
        $this->assertDatabaseHas('auditorias', [
            'accion' => 'SOLICITUD_RESPUESTAS_EDITADAS',
            'entidad' => 'SOLICITUD',
            'entidad_id' => $this->solicitudId,
        ]);
    }

    public function test_rechaza_una_opcion_que_no_pertenece_al_campo(): void
    {
        $this->put(route('solicitudes.respuestas.actualizar', [$this->solicitudId, $this->seccionId]), [
            'seccion' => $this->seccionId,
            'respuestas' => [
                $this->campoTextoId => 'Testigo Corregido',
                $this->campoRadioId => '9',
            ],
        ])->assertSessionHasErrors('respuestas.'.$this->campoRadioId);

        $this->assertDatabaseHas('solicitud_respuestas', [
            'solicitud_id' => $this->solicitudId,
            'campo_id' => $this->campoRadioId,
            'valor' => json_encode('1'),
        ]);
        $this->assertDatabaseCount('auditorias', 0);
    }

    public function test_no_permite_editar_una_solicitud_finalizada(): void
    {
        DB::table('solicitudes')->where('id', $this->solicitudId)->update(['solicitud_estado_id' => 2]);

        $this->put(route('solicitudes.datos-personales.actualizar', $this->solicitudId), [
            'nombres_solicitante' => 'Ana Corregida',
            'apellido_paterno_solicitante' => 'García',
            'apellido_materno_solicitante' => 'López',
            'curp_consulta' => 'GARC900101HMCXXX01',
            'email_contacto' => 'ana@example.test',
            'telefono_contacto' => '5512345678',
        ])->assertStatus(422);

        $this->put(route('solicitudes.respuestas.actualizar', [$this->solicitudId, $this->seccionId]), [
            'seccion' => $this->seccionId,
            'respuestas' => [
                $this->campoTextoId => 'Testigo Corregido',
                $this->campoRadioId => '2',
            ],
        ])->assertStatus(422);

        $this->assertDatabaseHas('solicitudes', [
            'id' => $this->solicitudId,
            'nombres_solicitante' => 'Ana',
        ]);
        $this->assertDatabaseHas('solicitud_respuestas', [
            'solicitud_id' => $this->solicitudId,
            'campo_id' => $this->campoTextoId,
            'valor' => json_encode('Original'),
        ]);
        $this->assertDatabaseCount('auditorias', 0);
    }

    public function test_no_permite_editar_una_seccion_de_otra_version(): void
    {
        $otraSeccionId = (string) Str::uuid();

        DB::table('formulario_secciones')->insert([
            'id' => $otraSeccionId,
            'formulario_version_id' => (string) Str::uuid(),
            'clave' => 'otra',
            'titulo' => 'Otra sección',
            'orden' => 1,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->put(route('solicitudes.respuestas.actualizar', [$this->solicitudId, $otraSeccionId]), [
            'seccion' => $otraSeccionId,
            'respuestas' => [],
        ])->assertNotFound();
    }

    private function crearSolicitudConFormulario(): void
    {
        $versionId = (string) Str::uuid();
        $this->solicitudId = (string) Str::uuid();
        $this->seccionId = (string) Str::uuid();
        $this->campoTextoId = (string) Str::uuid();
        $this->campoRadioId = (string) Str::uuid();

        DB::table('solicitud_estados')->insert([
            ['id' => 1, 'clave' => 'ENVIADA', 'nombre' => 'Enviada', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'clave' => 'APROBADA', 'nombre' => 'Aprobada', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('solicitudes')->insert([
            'id' => $this->solicitudId,
            'folio' => 'SOL-TEST-EDIT',
            'tramite_id' => 1,
            'formulario_version_id' => $versionId,
            'solicitud_estado_id' => 1,
            'curp_consulta' => 'GARC900101HMCXXX01',
            'email_contacto' => 'ana@example.test',
            'telefono_contacto' => '5512345678',
            'nombre_solicitante_snapshot' => 'Ana García López',
            'nombres_solicitante' => 'Ana',
            'apellido_paterno_solicitante' => 'García',
            'apellido_materno_solicitante' => 'López',
            'flujo_pago' => 'SIN_PAGO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('formulario_secciones')->insert([
            'id' => $this->seccionId,
            'formulario_version_id' => $versionId,
            'clave' => 'datos_testigo',
            'titulo' => 'Datos del testigo',
            'orden' => 1,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('formulario_campos')->insert([
            [
                'id' => $this->campoTextoId,
                'seccion_id' => $this->seccionId,
                'clave' => 'nombre_testigo',
                'etiqueta' => 'Nombre del testigo',
                'tipo' => 'TEXT',
                'requerido' => true,
                'orden' => 1,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $this->campoRadioId,
                'seccion_id' => $this->seccionId,
                'clave' => 'sexo',
                'etiqueta' => 'Sexo',
                'tipo' => 'RADIO',
                'requerido' => true,
                'orden' => 2,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('campo_opciones')->insert([
            ['id' => (string) Str::uuid(), 'campo_id' => $this->campoRadioId, 'valor' => '1', 'etiqueta' => 'Femenino', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => (string) Str::uuid(), 'campo_id' => $this->campoRadioId, 'valor' => '2', 'etiqueta' => 'Masculino', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('solicitud_respuestas')->insert([
            ['id' => (string) Str::uuid(), 'solicitud_id' => $this->solicitudId, 'campo_id' => $this->campoTextoId, 'valor' => json_encode('Original'), 'created_at' => now(), 'updated_at' => now()],
            ['id' => (string) Str::uuid(), 'solicitud_id' => $this->solicitudId, 'campo_id' => $this->campoRadioId, 'valor' => json_encode('1'), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function crearEsquema(): void
    {
        Schema::create('solicitud_estados', function (Blueprint $table): void {
            $table->id();
            $table->string('clave');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('es_final')->default(false);
            $table->boolean('activo')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('solicitudes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('folio', 50);
            $table->unsignedBigInteger('tramite_id');
            $table->uuid('formulario_version_id');
            $table->unsignedBigInteger('solicitud_estado_id');
            $table->string('curp_consulta', 18);
            $table->string('email_contacto', 200);
            $table->string('telefono_contacto', 30)->nullable();
            $table->string('nombre_solicitante_snapshot')->nullable();
            $table->string('nombres_solicitante')->nullable();
            $table->string('apellido_paterno_solicitante')->nullable();
            $table->string('apellido_materno_solicitante')->nullable();
            $table->string('flujo_pago', 30)->default('SIN_PAGO');
            $table->decimal('importe_pago', 12, 2)->nullable();
            $table->unsignedSmallInteger('pago_vigencia_dias')->nullable();
            $table->timestamp('fecha_envio')->nullable();
            $table->timestamp('fecha_terminacion')->nullable();
            $table->timestamps();
        });
        Schema::create('formulario_secciones', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('formulario_version_id');
            $table->string('clave');
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('formulario_campos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('seccion_id');
            $table->string('clave');
            $table->string('etiqueta');
            $table->string('tipo');
            $table->boolean('requerido')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->json('configuracion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('campo_opciones', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('campo_id');
            $table->string('valor');
            $table->string('etiqueta');
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('solicitud_respuestas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('solicitud_id');
            $table->uuid('campo_id');
            $table->json('valor')->nullable();
            $table->timestamps();
        });
        Schema::create('auditorias', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('actor_accesos_id', 100)->nullable();
            $table->string('actor_nombre_snapshot', 200)->nullable();
            $table->string('actor_departamento_snapshot', 200)->nullable();
            $table->string('accion', 100);
            $table->string('entidad', 100);
            $table->uuid('entidad_id')->nullable();
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at');
        });
    }
}
