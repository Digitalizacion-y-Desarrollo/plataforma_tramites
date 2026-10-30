<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActaNacimientoOcrTest extends TestCase
{
    private const CONTENIDO_ACTA = '%PDF-1.4 contenido de prueba del acta';

    private string $solicitudId;

    private string $campoFuenteId;

    private string $campoTextoId;

    private string $campoDomicilioId;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.openai.api_key' => 'clave-de-prueba', 'services.openai.model' => 'gpt-4o']);

        $this->crearEsquema();
        $this->crearSolicitudConCaptura();

        Storage::fake('local');
        Storage::disk('local')->put($this->storageKey(), self::CONTENIDO_ACTA);

        $this->withSession(['accesos' => ['id' => 'superuser-1', 'roles' => ['superuser']]]);
    }

    public function test_mapea_los_datos_extraidos_a_los_campos_de_la_captura(): void
    {
        $this->fakeOpenai(true, '', [
            'numero_acta' => '0123',
            'lugar_nacimiento' => 'Estado de México',
            'nombre_padre' => 'Juan Pérez',
            'nombre_madre' => 'María García',
            'no_libro' => '58',
        ]);

        $respuesta = $this->postJson(route('solicitudes.acta-ocr', $this->solicitudId));

        $respuesta->assertOk()
            ->assertJsonStructure(['es_acta_nacimiento', 'motivo', 'datos'])
            ->assertJson([
                'es_acta_nacimiento' => true,
                'datos' => [
                    $this->campoTextoId => '0123',
                ],
            ]);

        $this->assertArrayNotHasKey($this->campoDomicilioId, $respuesta->json('datos'));

        Http::assertSent(function ($request) {
            $datos = json_decode((string) $request->body(), true) ?: [];

            return str_starts_with((string) $request->url(), 'https://api.openai.com/')
                && ($datos['model'] ?? null) === 'gpt-4o'
                && is_array($datos['input'][1]['content'] ?? null)
                && ($datos['input'][1]['content'][0]['filename'] ?? null) === 'acta-nacimiento.pdf'
                && str_contains((string) ($datos['input'][1]['content'][0]['file_data'] ?? ''), 'base64,');
        });

        $this->assertDatabaseHas('auditorias', [
            'accion' => 'ACTA_OCR_EXTRAIDA',
            'entidad' => 'SOLICITUD',
            'entidad_id' => $this->solicitudId,
        ]);
    }

    public function test_no_rellena_cuando_el_documento_no_es_un_acta(): void
    {
        $this->fakeOpenai(false, 'El documento es un comprobante de domicilio.', []);

        $respuesta = $this->postJson(route('solicitudes.acta-ocr', $this->solicitudId));

        $respuesta->assertOk()->assertJson([
            'es_acta_nacimiento' => false,
            'motivo' => 'El documento es un comprobante de domicilio.',
            'datos' => [],
        ]);

        $this->assertDatabaseHas('auditorias', [
            'accion' => 'ACTA_OCR_EXTRAIDA',
            'entidad_id' => $this->solicitudId,
        ]);
    }

    public function test_rechaza_un_archivo_de_formato_no_admitido(): void
    {
        Storage::disk('local')->put($this->storageKey(), 'contenido en texto plano');
        Http::fake();

        $respuesta = $this->postJson(route('solicitudes.acta-ocr', $this->solicitudId));

        $respuesta->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_responde_404_cuando_no_hay_acta_adjunta(): void
    {
        DB::table('solicitud_respuestas')->where('solicitud_id', $this->solicitudId)->delete();

        $respuesta = $this->postJson(route('solicitudes.acta-ocr', $this->solicitudId));

        $respuesta->assertStatus(404);
    }

    public function test_responde_503_cuando_falta_la_credencial_de_openai(): void
    {
        config(['services.openai.api_key' => null]);

        $respuesta = $this->postJson(route('solicitudes.acta-ocr', $this->solicitudId));

        $respuesta->assertStatus(503);
    }

    public function test_rechaza_una_sesion_sin_rol_autorizado(): void
    {
        $this->withSession(['accesos' => ['id' => 'colaborador-1', 'roles' => ['colaborador']]]);

        $respuesta = $this->postJson(route('solicitudes.acta-ocr', $this->solicitudId));

        $respuesta->assertStatus(403);
        Http::assertNothingSent();
    }

    private function fakeOpenai(bool $esActa, string $motivo, array $datos): void
    {
        Http::fake([
            'https://api.openai.com/*' => Http::response([
                'id' => 'resp_prueba',
                'status' => 'completed',
                'output' => [
                    ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode([
                        'es_acta_nacimiento' => $esActa,
                        'motivo' => $motivo,
                        'datos' => $datos,
                    ], JSON_UNESCAPED_UNICODE)]]],
                ],
            ], 200),
        ]);
    }

    private function storageKey(): string
    {
        return 'solicitudes/'.$this->solicitudId.'/acta-prueba.pdf';
    }

    private function crearSolicitudConCaptura(): void
    {
        $versionId = (string) Str::uuid();
        $this->solicitudId = (string) Str::uuid();
        $this->campoFuenteId = (string) Str::uuid();
        $this->campoTextoId = (string) Str::uuid();
        $this->campoDomicilioId = (string) Str::uuid();
        $capturaId = (string) Str::uuid();

        DB::table('solicitud_estados')->insert([
            ['id' => 1, 'clave' => 'ENVIADA', 'nombre' => 'Enviada', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('solicitudes')->insert([
            'id' => $this->solicitudId,
            'folio' => 'SOL-TEST-OCR',
            'tramite_id' => 1,
            'formulario_version_id' => $versionId,
            'solicitud_estado_id' => 1,
            'curp_consulta' => 'GARC900101HMCXXX01',
            'email_contacto' => 'ana@example.test',
            'nombre_solicitante_snapshot' => 'Ana García López',
            'departamento_accesos_id_snapshot' => '30',
            'flujo_pago' => 'SIN_PAGO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('formulario_capturas_administrativas')->insert([
            'id' => $capturaId,
            'formulario_version_id' => $versionId,
            'campo_fuente_id' => $this->campoFuenteId,
            'clave' => 'datos_acta_nacimiento',
            'titulo' => 'Datos extraídos del acta de nacimiento',
            'orden' => 1,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('formulario_captura_administrativa_campos')->insert([
            [
                'id' => $this->campoTextoId,
                'captura_administrativa_id' => $capturaId,
                'clave' => 'numero_acta',
                'etiqueta' => 'Número de acta',
                'tipo' => 'TEXT',
                'requerido' => true,
                'orden' => 1,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $this->campoDomicilioId,
                'captura_administrativa_id' => $capturaId,
                'clave' => 'domicilio_residencia',
                'etiqueta' => 'Domicilio de residencia',
                'tipo' => 'DOMICILIO',
                'requerido' => false,
                'orden' => 2,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('solicitud_respuestas')->insert([
            'id' => (string) Str::uuid(),
            'solicitud_id' => $this->solicitudId,
            'campo_id' => $this->campoFuenteId,
            'valor' => json_encode(['nombre_original' => 'acta-prueba.pdf', 'storage_key' => $this->storageKey(), 'mime_type' => 'application/pdf', 'tamano_bytes' => strlen(self::CONTENIDO_ACTA)]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearEsquema(): void
    {
        Schema::create('solicitud_estados', function (Blueprint $table): void {
            $table->id();
            $table->string('clave');
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('areas_operativas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('accesos_departamento_id', 100);
            $table->string('nombre_snapshot');
            $table->string('siglas_snapshot')->nullable();
            $table->boolean('activa')->default(false);
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
            $table->string('nombre_solicitante_snapshot')->nullable();
            $table->string('departamento_accesos_id_snapshot', 100)->nullable();
            $table->string('flujo_pago', 30)->default('SIN_PAGO');
            $table->timestamp('fecha_envio')->nullable();
            $table->timestamp('fecha_terminacion')->nullable();
            $table->timestamps();
        });
        Schema::create('formulario_capturas_administrativas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('formulario_version_id');
            $table->uuid('campo_fuente_id')->nullable();
            $table->string('clave');
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('formulario_captura_administrativa_campos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('captura_administrativa_id');
            $table->string('clave');
            $table->string('etiqueta');
            $table->string('tipo')->default('TEXT');
            $table->boolean('requerido')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->json('configuracion')->nullable();
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
