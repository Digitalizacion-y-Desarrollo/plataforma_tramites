<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Tramite;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiKeysTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        Schema::create('api_keys', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('nombre', 150);
            $table->string('prefijo', 24)->unique();
            $table->char('token_hash', 64)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamp('expira_en')->nullable();
            $table->timestamp('ultimo_uso_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tramites', function (Blueprint $table): void {
            $table->id();
            $table->string('clave', 50)->unique();
            $table->string('nombre', 200);
            $table->string('departamento_accesos_id', 100)->nullable();
            $table->string('departamento_nombre_snapshot', 200)->nullable();
            $table->boolean('activo')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('solicitud_estados', function (Blueprint $table): void {
            $table->id();
            $table->string('clave', 50)->unique();
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('solicitudes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('folio', 50)->unique();
            $table->unsignedBigInteger('tramite_id');
            $table->string('departamento_accesos_id_snapshot', 100)->nullable();
            $table->string('departamento_nombre_snapshot', 200)->nullable();
            $table->uuid('formulario_version_id')->nullable();
            $table->unsignedBigInteger('solicitud_estado_id');
            $table->string('curp_consulta', 18);
            $table->string('email_contacto', 200);
            $table->string('telefono_contacto', 30)->nullable();
            $table->string('nombre_solicitante_snapshot', 300)->nullable();
            $table->string('nombres_solicitante', 100)->nullable();
            $table->string('apellido_paterno_solicitante', 100)->nullable();
            $table->string('apellido_materno_solicitante', 100)->nullable();
            $table->string('flujo_pago', 30)->nullable();
            $table->decimal('importe_pago', 12, 2)->nullable();
            $table->unsignedSmallInteger('pago_vigencia_dias')->nullable();
            $table->timestamp('fecha_envio')->nullable();
            $table->timestamp('fecha_terminacion')->nullable();
            $table->timestamps();
        });
        Schema::create('auditorias', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('actor_accesos_id')->nullable();
            $table->string('actor_nombre_snapshot')->nullable();
            $table->string('actor_departamento_snapshot')->nullable();
            $table->string('accion');
            $table->string('entidad');
            $table->string('entidad_id')->nullable();
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->string('ip')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function test_api_keys_are_administered_by_authorized_staff_and_plaintext_is_shown_once(): void
    {
        $this->get(route('api-keys.index'))->assertRedirect(route('accesos.login'));

        $this->withSession(['accesos' => ['id' => 'funcionario-1', 'nombre' => 'Prueba', 'roles' => ['superadmin']]])
            ->post(route('api-keys.store'), ['nombre' => 'Sistema de reportes'])
            ->assertRedirect(route('api-keys.index'))
            ->assertSessionHas('api_key_generada', fn (string $token): bool => str_starts_with($token, 'pt_live_'));

        $apiKey = ApiKey::firstOrFail();
        $this->assertNotEmpty($apiKey->token_hash);
        $this->assertDatabaseHas('auditorias', ['accion' => 'API_KEY_CREADA', 'entidad_id' => $apiKey->id]);
        $this->get(route('api-keys.index'))->assertOk()->assertSee('Sistema de reportes')->assertDontSee($apiKey->token_hash);
    }

    public function test_api_returns_all_requests_and_accepts_area_state_and_procedure_filters(): void
    {
        [$apiKey, $token] = ApiKey::generar('Sistema externo');
        $estadoEnviada = $this->crearEstado('ENVIADA', 'Enviada');
        $estadoAprobada = $this->crearEstado('APROBADA', 'Aprobada');
        $tramiteConstruccion = Tramite::create(['clave' => 'LIC_UNICA_CONSTRUCCION', 'nombre' => 'Licencia Única de Construcción']);
        $tramiteResidencia = Tramite::create(['clave' => 'CONST_RESIDENCIA', 'nombre' => 'Constancia de residencia']);
        $solicitudCoincidente = $this->crearSolicitud($tramiteConstruccion->id, $estadoEnviada, '1', 'NEZA-001');
        $this->crearSolicitud($tramiteResidencia->id, $estadoAprobada, '30', 'NEZA-002');

        $this->getJson('/api/v1/solicitudes', ['Authorization' => 'Bearer '.$token])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.solicitante.curp', 'AAAA000101HDFBBB01');

        $this->getJson('/api/v1/solicitudes?area_id=1&estado_id='.$estadoEnviada.'&tramite_id='.$tramiteConstruccion->id, ['Authorization' => 'Bearer '.$token])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $solicitudCoincidente)
            ->assertJsonPath('data.0.area.nombre', 'DIRECCIÓN DE DESARROLLO URBANO')
            ->assertJsonPath('data.0.tramite.clave', 'LIC_UNICA_CONSTRUCCION')
            ->assertJsonPath('data.0.estado.clave', 'ENVIADA');

        $this->assertNotNull($apiKey->fresh()->ultimo_uso_at);
    }

    public function test_api_rejects_missing_revoked_or_expired_keys(): void
    {
        $this->getJson('/api/v1/solicitudes')->assertUnauthorized();

        [, $tokenRevocado] = ApiKey::generar('Revocada');
        $apiKeyRevocada = ApiKey::where('token_hash', hash('sha256', $tokenRevocado))->firstOrFail();
        $apiKeyRevocada->update(['activo' => false]);
        $this->getJson('/api/v1/solicitudes', ['Authorization' => 'Bearer '.$tokenRevocado])->assertUnauthorized();

        [, $tokenExpirado] = ApiKey::generar('Expirada', now()->subDay()->toDateTimeString());
        $this->getJson('/api/v1/solicitudes', ['Authorization' => 'Bearer '.$tokenExpirado])->assertUnauthorized();
    }

    private function crearEstado(string $clave, string $nombre): int
    {
        return DB::table('solicitud_estados')->insertGetId([
            'clave' => $clave,
            'nombre' => $nombre,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearSolicitud(int $tramiteId, int $estadoId, string $areaId, string $folio): string
    {
        $id = (string) Str::uuid();
        DB::table('solicitudes')->insert([
            'id' => $id,
            'folio' => $folio,
            'tramite_id' => $tramiteId,
            'departamento_accesos_id_snapshot' => $areaId,
            'departamento_nombre_snapshot' => $areaId === '1' ? 'DIRECCIÓN DE DESARROLLO URBANO' : 'SECRETARÍA DEL H. AYUNTAMIENTO',
            'formulario_version_id' => (string) Str::uuid(),
            'solicitud_estado_id' => $estadoId,
            'curp_consulta' => 'AAAA000101HDFBBB01',
            'email_contacto' => 'ciudadano@example.test',
            'telefono_contacto' => '5555555555',
            'nombres_solicitante' => 'Ana',
            'apellido_paterno_solicitante' => 'Prueba',
            'apellido_materno_solicitante' => 'Ciudadana',
            'flujo_pago' => 'PAGO_DIRECTO',
            'importe_pago' => 117.31,
            'pago_vigencia_dias' => 30,
            'fecha_envio' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
