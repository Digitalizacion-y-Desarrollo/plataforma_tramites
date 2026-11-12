<?php

namespace Tests\Feature;

use App\Models\Formulario;
use App\Models\Tramite;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SolicitudDomiciliosTest extends TestCase
{
    private const CURP = 'GARC900101HMCXXX01';

    private ?string $versionId = null;

    private ?string $municipioId = null;

    private ?string $regimenFiscalId = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->crearEsquemaDeSolicitud();
        Mail::fake();
        config([
            'services.tesoreria.url' => 'https://tesoreria.test/api',
            'services.tesoreria.email' => 'integracion@municipio.gob.mx',
            'services.tesoreria.password' => 'secreto',
        ]);
        Http::fake([
            'https://tesoreria.test/api/auth/login' => Http::response(['access_token' => 'token-prueba'], 200),
            'https://tesoreria.test/api/contribuyentes' => Http::response(['success' => true, 'data' => ['id' => 125]], 201),
        ]);
        $this->withSession(['accesos' => ['id' => 'superuser-1', 'roles' => ['superuser']]]);
    }

    public function test_formulario_ciudadano_muestra_domicilio_fuera_de_neza_sin_autocompletado(): void
    {
        [$tramite, $formulario, $campoId] = $this->crearFormulario();

        $respuesta = $this->get(route('portal.tramites.iniciar', $tramite))
            ->assertOk()
            ->assertSee('Colonia')
            ->assertSee('Código postal')
            ->assertSee('Municipio')
            ->assertSee('Calle')
            ->assertSee('Número')
            ->assertSee('respuestas['.$campoId.'][codigo_postal]', false)
            ->assertSee('respuestas['.$campoId.'][municipio]', false)
            ->assertDontSee('data-domicilio')
            ->assertDontSee('latitud');

        $respuesta->assertDontSee('readonly');

        $this->get(route('formulario.show', $formulario))
            ->assertOk()
            ->assertSee('Domicilio fuera de Nezahualcóyotl')
            ->assertSee('Código postal')
            ->assertSee('Municipio')
            ->assertDontSee('data-domicilio');
    }

    public function test_consulta_el_expediente_por_curp_y_tipo_de_persona_para_prellenar_datos(): void
    {
        DB::table('expedientes')->insert([
            'id' => (string) Str::uuid(),
            'curp_consulta' => self::CURP,
            'tipo_persona' => 'FISICA',
            'email_contacto' => 'ana@example.test',
            'telefono_contacto' => '5512345678',
            'nombres_solicitante' => 'Ana',
            'apellido_paterno_solicitante' => 'García',
            'apellido_materno_solicitante' => 'López',
            'calle' => 'Avenida Nezahualcóyotl',
            'numero_exterior' => '120',
            'colonia' => 'Benito Juárez',
            'codigo_postal' => '57000',
            'municipio_id' => 715,
            'regimen_fiscal_id' => $this->regimenFiscalId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson(route('portal.tramites.expedientes.verificar', ['curp' => self::CURP, 'tipo_persona' => 'FISICA']))
            ->assertOk()
            ->assertJsonPath('existe', true)
            ->assertJsonPath('datos.email_contacto', 'ana@example.test')
            ->assertJsonPath('datos.municipio_id', 715);

        $this->getJson(route('portal.tramites.expedientes.verificar', ['curp' => self::CURP, 'tipo_persona' => 'MORAL']))
            ->assertOk()
            ->assertJsonPath('existe', false);
    }

    public function test_documento_de_diez_megabytes_es_aceptado(): void
    {
        Storage::fake('local');
        [$tramite, , $campoId] = $this->crearFormulario('FILE', 'Documento');
        $datos = $this->datosSolicitud($campoId, []);
        $datos['respuestas'][$campoId] = UploadedFile::fake()->create('documento.pdf', 10240, 'application/pdf');

        $this->post(route('portal.tramites.solicitudes.store', $tramite), $datos)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('solicitudes', 1);
        $this->assertDatabaseCount('expedientes', 1);
        $this->assertDatabaseHas('solicitudes', ['expediente_id' => DB::table('expedientes')->value('id')]);
        $this->assertDatabaseHas('expedientes', ['id_externo' => '125']);
        $this->assertDatabaseHas('auditorias', ['accion' => 'EXPEDIENTE_CREADO', 'entidad' => 'EXPEDIENTE']);
    }

    public function test_documento_mayor_a_diez_megabytes_es_rechazado(): void
    {
        [$tramite, , $campoId] = $this->crearFormulario('FILE', 'Documento');
        $datos = $this->datosSolicitud($campoId, []);
        $datos['respuestas'][$campoId] = UploadedFile::fake()->create('documento.pdf', 10241, 'application/pdf');

        $this->post(route('portal.tramites.solicitudes.store', $tramite), $datos)
            ->assertSessionHasErrors('respuestas.'.$campoId);

        $this->assertDatabaseCount('solicitudes', 0);
    }

    public function test_formulario_muestra_patron_numerico_en_codigo_postal_y_numero(): void
    {
        [$tramite, , $campoId] = $this->crearFormulario('DOMICILIO_FUERA_NEZA');

        $this->get(route('portal.tramites.iniciar', $tramite))
            ->assertOk()
            ->assertSee('inputmode="numeric"', false)
            ->assertSee('pattern="[0-9]{5}"', false)
            ->assertSee('pattern="[0-9]{1,50}"', false);
    }

    public function test_solicitud_rechaza_un_numero_con_letras(): void
    {
        [$tramite, , $campoId] = $this->crearFormulario('DOMICILIO_FUERA_NEZA', 'Domicilio fuera de Nezahualcóyotl');
        $domicilio = [
            'colonia' => 'Centro',
            'codigo_postal' => '06000',
            'municipio' => 'Cuauhtémoc',
            'calle' => 'Avenida Juárez',
            'numero' => '12 bis',
        ];

        $this->post(route('portal.tramites.solicitudes.store', $tramite), $this->datosSolicitud($campoId, $domicilio))
            ->assertSessionHasErrors('respuestas.'.$campoId);

        $this->assertDatabaseCount('solicitudes', 0);
    }

    public function test_solicitud_acepta_un_domicilio_fuera_de_nezahualcoyotl_valido(): void
    {
        [$tramite, , $campoId] = $this->crearFormulario();
        $domicilio = [
            'colonia' => 'Centro',
            'codigo_postal' => '06000',
            'municipio' => 'Cuauhtémoc',
            'calle' => 'Avenida Juárez',
            'numero' => '123',
        ];

        $this->post(route('portal.tramites.solicitudes.store', $tramite), $this->datosSolicitud($campoId, $domicilio))
            ->assertRedirect(route('portal.tramites.iniciar', $tramite));

        $this->assertDatabaseHas('solicitud_respuestas', ['campo_id' => $campoId]);
        $folio = DB::table('solicitudes')->value('folio');
        $this->get(route('portal.tramites.iniciar', $tramite))
            ->assertOk()
            ->assertSee('Solicitud enviada')
            ->assertSee($folio)
            ->assertSee('Regresar al portal de trámites')
            ->assertSee(route('portal.publico'))
            ->assertDontSee('Completa tu solicitud');
        $valor = DB::table('solicitud_respuestas')->where('campo_id', $campoId)->value('valor');
        $this->assertSame($domicilio, json_decode($valor, true));
        $this->assertNotNull(DB::table('solicitudes')->value('aviso_privacidad_aceptado_at'));
    }

    public function test_solicitud_rechaza_un_codigo_postal_que_no_tiene_cinco_digitos(): void
    {
        [$tramite, , $campoId] = $this->crearFormulario();
        $domicilio = [
            'colonia' => 'Centro',
            'codigo_postal' => '6000',
            'municipio' => 'Cuauhtémoc',
            'calle' => 'Avenida Juárez',
            'numero' => '123',
        ];

        $this->post(route('portal.tramites.solicitudes.store', $tramite), $this->datosSolicitud($campoId, $domicilio))
            ->assertSessionHasErrors('respuestas.'.$campoId);

        $this->assertDatabaseCount('solicitudes', 0);
        $this->assertDatabaseCount('solicitud_respuestas', 0);
    }

    public function test_solicitud_rechaza_un_domicilio_fuera_de_neza_que_no_es_un_arreglo(): void
    {
        [$tramite, , $campoId] = $this->crearFormulario();
        $datos = $this->datosSolicitud($campoId, []);
        $datos['respuestas'][$campoId] = 'Avenida Juárez 123';

        $this->post(route('portal.tramites.solicitudes.store', $tramite), $datos)
            ->assertSessionHasErrors('respuestas.'.$campoId);

        $this->assertDatabaseCount('solicitudes', 0);
        $this->assertDatabaseCount('solicitud_respuestas', 0);
    }

    public function test_solicitud_rechaza_un_subcampo_del_domicilio_fuera_de_neza_que_excede_la_longitud_maxima(): void
    {
        [$tramite, , $campoId] = $this->crearFormulario();
        $domicilio = [
            'colonia' => str_repeat('A', 101),
            'codigo_postal' => '06000',
            'municipio' => 'Cuauhtémoc',
            'calle' => 'Avenida Juárez',
            'numero' => '123',
        ];

        $this->post(route('portal.tramites.solicitudes.store', $tramite), $this->datosSolicitud($campoId, $domicilio))
            ->assertSessionHasErrors('respuestas.'.$campoId);

        $this->assertDatabaseCount('solicitudes', 0);
        $this->assertDatabaseCount('solicitud_respuestas', 0);
    }

    public function test_solicitud_descarta_coordenadas_y_datos_ajenos_al_domicilio_fuera_de_neza(): void
    {
        [$tramite, , $campoId] = $this->crearFormulario();
        $domicilio = [
            'colonia' => 'Centro',
            'codigo_postal' => '06000',
            'municipio' => 'Cuauhtémoc',
            'calle' => 'Avenida Juárez',
            'numero' => '123',
        ];

        $this->post(route('portal.tramites.solicitudes.store', $tramite), $this->datosSolicitud($campoId, $domicilio + [
            'latitud' => '19.4326',
            'longitud' => '-99.1332',
            'pais' => 'MX',
        ]))->assertRedirect(route('portal.tramites.iniciar', $tramite));

        $valor = DB::table('solicitud_respuestas')->where('campo_id', $campoId)->value('valor');
        $this->assertSame($domicilio, json_decode($valor, true));
    }

    public function test_formulario_ciudadano_muestra_domicilio_usa_con_estado_y_zip(): void
    {
        [$tramite, $formulario, $campoId] = $this->crearFormulario('DOMICILIO_USA', 'Domicilio en Estados Unidos');

        $this->get(route('portal.tramites.iniciar', $tramite))
            ->assertOk()
            ->assertSee('Domicilio en Estados Unidos')
            ->assertSee('Dirección (calle y número)')
            ->assertSee('Apartamento, suite o unidad (opcional)')
            ->assertSee('Ciudad')
            ->assertSee('Estado')
            ->assertSee('Código postal (ZIP)')
            ->assertSee('respuestas['.$campoId.'][estado]', false)
            ->assertSee('California')
            ->assertSee('País: Estados Unidos')
            ->assertDontSee('data-domicilio')
            ->assertDontSee('capa.json');

        $this->get(route('formulario.show', $formulario))
            ->assertOk()
            ->assertSee('Domicilio en Estados Unidos')
            ->assertSee('Código postal (ZIP)')
            ->assertSee('Selecciona un estado')
            ->assertSee('California')
            ->assertDontSee('data-domicilio');
    }

    public function test_solicitud_acepta_un_domicilio_en_estados_unidos_valido(): void
    {
        [$tramite, , $campoId] = $this->crearFormulario('DOMICILIO_USA', 'Domicilio en Estados Unidos');
        $domicilio = [
            'calle' => '123 Main St',
            'numero' => 'Apt 4B',
            'ciudad' => 'Los Angeles',
            'estado' => 'CA',
            'codigo_postal' => '90012-1234',
        ];

        $this->post(route('portal.tramites.solicitudes.store', $tramite), $this->datosSolicitud($campoId, $domicilio))
            ->assertRedirect(route('portal.tramites.iniciar', $tramite));

        $this->assertDatabaseHas('solicitud_respuestas', ['campo_id' => $campoId]);
        $valor = DB::table('solicitud_respuestas')->where('campo_id', $campoId)->value('valor');
        $this->assertSame($domicilio + ['pais' => 'US'], json_decode($valor, true));
    }

    public function test_solicitud_acepta_un_domicilio_en_estados_unidos_sin_apartamento(): void
    {
        [$tramite, , $campoId] = $this->crearFormulario('DOMICILIO_USA', 'Domicilio en Estados Unidos');
        $domicilio = [
            'calle' => '500 Congress Ave',
            'numero' => '',
            'ciudad' => 'Austin',
            'estado' => 'TX',
            'codigo_postal' => '78701',
        ];

        $this->post(route('portal.tramites.solicitudes.store', $tramite), $this->datosSolicitud($campoId, $domicilio))
            ->assertRedirect(route('portal.tramites.iniciar', $tramite));

        $this->assertDatabaseHas('solicitud_respuestas', ['campo_id' => $campoId]);
        $valor = DB::table('solicitud_respuestas')->where('campo_id', $campoId)->value('valor');
        $this->assertSame(
            ['calle' => '500 Congress Ave', 'numero' => null, 'ciudad' => 'Austin', 'estado' => 'TX', 'codigo_postal' => '78701', 'pais' => 'US'],
            json_decode($valor, true),
        );
    }

    public function test_solicitud_rechaza_un_zip_de_estados_unidos_invalido(): void
    {
        [$tramite, , $campoId] = $this->crearFormulario('DOMICILIO_USA', 'Domicilio en Estados Unidos');
        $domicilio = [
            'calle' => '123 Main St',
            'numero' => '',
            'ciudad' => 'Los Angeles',
            'estado' => 'CA',
            'codigo_postal' => '9001',
        ];

        $this->post(route('portal.tramites.solicitudes.store', $tramite), $this->datosSolicitud($campoId, $domicilio))
            ->assertSessionHasErrors('respuestas.'.$campoId);

        $this->assertDatabaseCount('solicitudes', 0);
        $this->assertDatabaseCount('solicitud_respuestas', 0);
    }

    public function test_solicitud_rechaza_un_estado_de_estados_unidos_invalido(): void
    {
        [$tramite, , $campoId] = $this->crearFormulario('DOMICILIO_USA', 'Domicilio en Estados Unidos');
        $domicilio = [
            'calle' => '123 Main St',
            'numero' => '',
            'ciudad' => 'Los Angeles',
            'estado' => 'XX',
            'codigo_postal' => '90012',
        ];

        $this->post(route('portal.tramites.solicitudes.store', $tramite), $this->datosSolicitud($campoId, $domicilio))
            ->assertSessionHasErrors('respuestas.'.$campoId);

        $this->assertDatabaseCount('solicitudes', 0);
        $this->assertDatabaseCount('solicitud_respuestas', 0);
    }

    public function test_solicitud_rechaza_una_version_de_formulario_que_ya_no_esta_vigente(): void
    {
        [$tramite, , $campoId] = $this->crearFormulario();
        $datos = $this->datosSolicitud($campoId, [
            'colonia' => 'Centro',
            'codigo_postal' => '06000',
            'municipio' => 'Cuauhtémoc',
            'calle' => 'Avenida Juárez',
            'numero' => '123',
        ]);
        $datos['formulario_version_id'] = (string) Str::uuid();

        $this->post(route('portal.tramites.solicitudes.store', $tramite), $datos)
            ->assertSessionHasErrors('formulario_version_id');

        $this->assertDatabaseCount('solicitudes', 0);
        $this->assertDatabaseCount('solicitud_respuestas', 0);
    }

    public function test_solicitud_exige_aceptar_el_aviso_de_privacidad(): void
    {
        [$tramite, , $campoId] = $this->crearFormulario();
        $datos = $this->datosSolicitud($campoId, [
            'colonia' => 'Centro',
            'codigo_postal' => '06000',
            'municipio' => 'Cuauhtémoc',
            'calle' => 'Avenida Juárez',
            'numero' => '123',
        ]);
        unset($datos['acepto_aviso_privacidad']);

        $this->post(route('portal.tramites.solicitudes.store', $tramite), $datos)
            ->assertSessionHasErrors('acepto_aviso_privacidad');

        $this->assertDatabaseCount('solicitudes', 0);
    }

    private function datosSolicitud(string $campoId, array $domicilio): array
    {
        return [
            'formulario_version_id' => $this->versionId,
            'tipo_persona' => 'FISICA',
            'nombres_solicitante' => 'Ana',
            'apellido_paterno_solicitante' => 'García',
            'apellido_materno_solicitante' => 'López',
            'curp_consulta' => self::CURP,
            'email_contacto' => 'ana@example.test',
            'telefono_contacto' => '5512345678',
            'razon_social' => null,
            'calle' => 'Avenida Nezahualcóyotl',
            'numero_exterior' => '120',
            'colonia' => 'Benito Juárez',
            'codigo_postal' => '57000',
            'municipio_id' => 715,
            'regimen_fiscal_id' => $this->regimenFiscalId,
            'acepto_aviso_privacidad' => '1',
            'respuestas' => [$campoId => $domicilio],
        ];
    }

    private function crearFormulario(
        string $tipo = 'DOMICILIO_FUERA_NEZA',
        string $etiqueta = 'Domicilio fuera de Nezahualcóyotl',
    ): array {
        $tramite = Tramite::query()->findOrFail(1);
        $formularioId = (string) Str::uuid();
        $versionId = (string) Str::uuid();
        $seccionId = (string) Str::uuid();
        $campoId = (string) Str::uuid();
        $this->versionId = $versionId;

        DB::table('formularios')->insert([
            'id' => $formularioId,
            'nombre' => 'Formulario de domicilio',
            'departamento_nombre_snapshot' => 'Desarrollo Urbano',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('formulario_versiones')->insert([
            'id' => $versionId,
            'formulario_id' => $formularioId,
            'formulario_estado_id' => 2,
            'numero_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('formulario_secciones')->insert([
            'id' => $seccionId,
            'formulario_version_id' => $versionId,
            'clave' => 'ubicacion',
            'titulo' => 'Ubicación',
            'orden' => 1,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('formulario_campos')->insert([
            'id' => $campoId,
            'seccion_id' => $seccionId,
            'clave' => 'domicilio_'.Str::lower($tipo),
            'etiqueta' => $etiqueta,
            'tipo' => $tipo,
            'requerido' => true,
            'orden' => 1,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('tramite_formulario')->insert([
            'id' => (string) Str::uuid(),
            'tramite_id' => $tramite->id,
            'formulario_version_id' => $versionId,
            'fecha_inicio_vigencia' => now(),
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tramite, Formulario::query()->findOrFail($formularioId), $campoId];
    }

    private function crearEsquemaDeSolicitud(): void
    {
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
        Schema::create('formulario_estados', function (Blueprint $table): void {
            $table->id();
            $table->string('clave');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('formularios', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('departamento_accesos_id')->nullable();
            $table->string('departamento_nombre_snapshot')->nullable();
            $table->string('subdepartamento_accesos_id')->nullable();
            $table->string('subdepartamento_nombre_snapshot')->nullable();
            $table->string('creado_por_accesos_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('formulario_versiones', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('formulario_id');
            $table->unsignedBigInteger('formulario_estado_id');
            $table->unsignedInteger('numero_version');
            $table->timestamp('fecha_publicacion')->nullable();
            $table->string('creado_por_accesos_id')->nullable();
            $table->string('publicado_por_accesos_id')->nullable();
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
        Schema::create('formulario_grupos_campos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('formulario_version_id');
            $table->string('titulo');
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('formulario_grupo_campo', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('grupo_id');
            $table->uuid('campo_id');
            $table->timestamps();
        });
        Schema::create('campo_condiciones', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('campo_destino_id')->nullable();
            $table->uuid('campo_origen_id')->nullable();
            $table->uuid('grupo_destino_id')->nullable();
            $table->string('operador');
            $table->json('valor_comparacion')->nullable();
            $table->string('accion');
            $table->unsignedInteger('grupo')->nullable();
            $table->timestamps();
        });
        Schema::create('tramite_formulario', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tramite_id');
            $table->uuid('formulario_version_id');
            $table->timestamp('fecha_inicio_vigencia')->nullable();
            $table->timestamp('fecha_fin_vigencia')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
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
        Schema::create('notificacion_estados', function (Blueprint $table): void {
            $table->id();
            $table->string('clave');
            $table->string('nombre');
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('municipios', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('nombre', 120);
            $table->string('estado', 120)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('regimenes_fiscales', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('clave', 10);
            $table->string('nombre', 120);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        $this->municipioId = (string) Str::uuid();
        DB::table('municipios')->insert(['id' => $this->municipioId, 'nombre' => 'Nezahualcóyotl', 'estado' => 'Estado de México', 'activo' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->regimenFiscalId = (string) Str::uuid();
        DB::table('regimenes_fiscales')->insert(['id' => $this->regimenFiscalId, 'clave' => '616', 'nombre' => 'Sin obligaciones fiscales', 'activo' => true, 'created_at' => now(), 'updated_at' => now()]);
        Schema::create('expedientes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('curp_consulta', 18);
            $table->string('tipo_persona', 10);
            $table->string('id_externo', 100)->nullable();
            $table->string('email_contacto', 200);
            $table->string('telefono_contacto', 30)->nullable();
            $table->string('nombres_solicitante', 100)->nullable();
            $table->string('apellido_paterno_solicitante', 100)->nullable();
            $table->string('apellido_materno_solicitante', 100)->nullable();
            $table->string('razon_social', 200)->nullable();
            $table->string('calle', 200);
            $table->string('numero_exterior', 30);
            $table->string('colonia', 200);
            $table->string('codigo_postal', 10);
            $table->uuid('municipio_id');
            $table->uuid('regimen_fiscal_id');
            $table->timestamps();
            $table->unique(['curp_consulta', 'tipo_persona']);
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
        Schema::create('solicitudes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('expediente_id')->nullable();
            $table->string('folio', 50);
            $table->unsignedBigInteger('tramite_id');
            $table->string('departamento_accesos_id_snapshot')->nullable();
            $table->string('departamento_nombre_snapshot')->nullable();
            $table->uuid('formulario_version_id');
            $table->unsignedBigInteger('solicitud_estado_id');
            $table->string('curp_consulta', 18);
            $table->string('email_contacto', 200);
            $table->string('telefono_contacto', 30)->nullable();
            $table->string('nombre_solicitante_snapshot')->nullable();
            $table->string('nombres_solicitante')->nullable();
            $table->string('apellido_paterno_solicitante')->nullable();
            $table->string('apellido_materno_solicitante')->nullable();
            $table->string('tipo_persona', 10)->nullable();
            $table->string('razon_social', 200)->nullable();
            $table->string('calle', 200)->nullable();
            $table->string('numero_exterior', 30)->nullable();
            $table->string('colonia', 200)->nullable();
            $table->string('codigo_postal', 10)->nullable();
            $table->uuid('municipio_id')->nullable();
            $table->uuid('regimen_fiscal_id')->nullable();
            $table->string('flujo_pago', 30)->default('SIN_PAGO');
            $table->decimal('importe_pago', 12, 2)->nullable();
            $table->unsignedSmallInteger('pago_vigencia_dias')->nullable();
            $table->timestamp('fecha_envio')->nullable();
            $table->timestamp('fecha_terminacion')->nullable();
            $table->timestamp('aviso_privacidad_aceptado_at')->nullable();
            $table->timestamps();
        });
        Schema::create('solicitud_respuestas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('solicitud_id');
            $table->uuid('campo_id');
            $table->json('valor')->nullable();
            $table->timestamps();
        });
        Schema::create('solicitud_historial_estados', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('solicitud_id');
            $table->unsignedBigInteger('estado_anterior_id')->nullable();
            $table->unsignedBigInteger('estado_nuevo_id');
            $table->text('comentario')->nullable();
            $table->string('actor_tipo', 30);
            $table->string('actor_accesos_id')->nullable();
            $table->string('actor_nombre_snapshot')->nullable();
            $table->string('actor_departamento_snapshot')->nullable();
            $table->timestamp('fecha');
        });
        Schema::create('notificacion_emails', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('solicitud_id')->nullable();
            $table->unsignedBigInteger('notificacion_estado_id');
            $table->string('destinatario', 200);
            $table->string('tipo', 50);
            $table->string('asunto', 200);
            $table->string('proveedor_message_id')->nullable();
            $table->unsignedInteger('intentos')->default(0);
            $table->timestamp('fecha_envio')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        DB::table('tramites')->insert([
            'id' => 1,
            'clave' => 'CONST_REG',
            'nombre' => 'Constancia de regularización',
            'departamento_accesos_id' => 'du-1',
            'departamento_nombre_snapshot' => 'Desarrollo Urbano',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('formulario_estados')->insert([
            ['id' => 1, 'clave' => 'BORRADOR', 'nombre' => 'Borrador', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'clave' => 'PUBLICADO', 'nombre' => 'Publicado', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('solicitud_estados')->insert([
            'id' => 1,
            'clave' => 'ENVIADA',
            'nombre' => 'Enviada',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('notificacion_estados')->insert([
            ['id' => 1, 'clave' => 'PENDIENTE', 'nombre' => 'Pendiente', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'clave' => 'ENVIADO', 'nombre' => 'Enviado', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
