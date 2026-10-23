<?php

namespace Tests\Feature;

use App\Mail\TokenConsultaMail;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConsultaCiudadanaTest extends TestCase
{
    private const CURP = 'GARC900101HMCXXX01';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->crearEsquemaDeConsulta();
        Mail::fake();
        RateLimiter::clear('consulta-token:'.hash('sha256', self::CURP.'|ana@example.test'));
    }

    public function test_valid_pair_can_enter_portal_and_only_see_its_own_requests(): void
    {
        $propia = $this->crearSolicitud('ana@example.test', 'SOL-ANA');
        $this->crearSolicitud('otra@example.test', 'SOL-OTRA');

        $this->post(route('consulta.token.solicitar'), ['curp' => self::CURP, 'email_contacto' => 'ANA@example.test'])
            ->assertRedirect();
        Mail::assertSent(TokenConsultaMail::class, 1);
        $token = $this->tokenEnviado();

        $this->post(route('consulta.token.verificar'), ['token' => $token])->assertRedirect(route('consulta.solicitudes'));
        $this->get(route('consulta.solicitudes'))->assertOk()->assertSee('Ana García')->assertSee('SOL-ANA')->assertDontSee('SOL-OTRA');
        $this->get(route('consulta.solicitudes.detalle', $propia))->assertOk()->assertSee('SOL-ANA');
        $this->post(route('consulta.cerrar'))->assertRedirect(route('portal.publico').'#consulta');
        $this->get(route('consulta.solicitudes'))->assertRedirect(route('portal.publico').'#consulta');
    }

    public function test_wrong_email_does_not_send_a_token_or_expose_the_registered_pair(): void
    {
        $this->crearSolicitud('ana@example.test', 'SOL-ANA');

        $this->post(route('consulta.token.solicitar'), ['curp' => self::CURP, 'email_contacto' => 'desconocido@example.test'])
            ->assertSessionHas('consulta_aviso')->assertSessionHas('consulta_pendiente');
        Mail::assertNothingSent();
        $this->get(route('portal.publico'))->assertOk()->assertSee('Paso 2 de 2')->assertDontSee('ana@example.test');
        $this->get(route('consulta.solicitudes'))->assertRedirect(route('portal.publico').'#consulta');
    }

    public function test_token_expires_and_cannot_be_reused(): void
    {
        $this->crearSolicitud('ana@example.test', 'SOL-ANA');
        $this->post(route('consulta.token.solicitar'), ['curp' => self::CURP, 'email_contacto' => 'ana@example.test']);
        $token = $this->tokenEnviado();

        $this->travel(16)->minutes();
        $this->post(route('consulta.token.verificar'), ['token' => $token])->assertSessionHas('consulta_aviso');
        $this->travelBack();
        $this->post(route('consulta.token.verificar'), ['token' => $token])->assertRedirect(route('portal.publico').'#consulta');
        $this->assertDatabaseHas('token_consultas', ['curp' => self::CURP, 'utilizado' => 1]);
    }

    public function test_detail_hides_other_email_and_internal_observations(): void
    {
        $propia = $this->crearSolicitud('ana@example.test', 'SOL-ANA');
        $ajena = $this->crearSolicitud('otra@example.test', 'SOL-OTRA');
        foreach ([['Pública', true], ['Interna', false]] as [$texto, $visible]) {
            DB::table('solicitud_observaciones')->insert([
                'id' => (string) Str::uuid(), 'solicitud_id' => $propia, 'texto' => $texto,
                'visible_ciudadano' => $visible, 'actor_accesos_id' => 'empleado-1',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $this->withSession(['consulta_ciudadana' => ['curp' => self::CURP, 'email' => 'ana@example.test', 'expira' => now()->addMinutes(30)->timestamp]]);

        $this->get(route('consulta.solicitudes.detalle', $propia))->assertOk()->assertSee('Pública')->assertDontSee('Interna');
        $this->get(route('consulta.solicitudes.detalle', $ajena))->assertNotFound();
        $this->travel(31)->minutes();
        $this->get(route('consulta.solicitudes'))->assertRedirect(route('portal.publico').'#consulta');
    }

    public function test_citizen_can_reply_once_to_the_latest_public_staff_comment(): void
    {
        $solicitud = $this->crearSolicitud('ana@example.test', 'SOL-ANA');
        $comentario = (string) Str::uuid();
        DB::table('solicitud_observaciones')->insert([
            'id' => $comentario,
            'solicitud_id' => $solicitud,
            'texto' => 'Comparte la aclaración solicitada.',
            'visible_ciudadano' => true,
            'actor_tipo' => 'SERVIDOR_PUBLICO',
            'actor_accesos_id' => 'empleado-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->withSession(['consulta_ciudadana' => ['curp' => self::CURP, 'email' => 'ana@example.test', 'expira' => now()->addMinutes(30)->timestamp]]);

        $this->get(route('consulta.solicitudes.detalle', $solicitud))
            ->assertOk()
            ->assertSee('Responder al área responsable');
        $this->post(route('consulta.solicitudes.comentarios.store', $solicitud), ['comentario' => 'Ya corregí la información solicitada.'])
            ->assertRedirect(route('consulta.solicitudes.detalle', $solicitud))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('solicitud_observaciones', [
            'solicitud_id' => $solicitud,
            'respuesta_a_id' => $comentario,
            'actor_tipo' => 'CIUDADANO',
            'texto' => 'Ya corregí la información solicitada.',
        ]);
        $this->get(route('consulta.solicitudes.detalle', $solicitud))
            ->assertOk()
            ->assertSee('Ya corregí la información solicitada.')
            ->assertDontSee('Responder al área responsable');
        $this->post(route('consulta.solicitudes.comentarios.store', $solicitud), ['comentario' => 'Segundo mensaje'])
            ->assertStatus(422);
    }

    public function test_citizen_cannot_reply_without_a_current_staff_comment_or_to_another_request(): void
    {
        $propia = $this->crearSolicitud('ana@example.test', 'SOL-ANA');
        $ajena = $this->crearSolicitud('otra@example.test', 'SOL-OTRA');
        $this->withSession(['consulta_ciudadana' => ['curp' => self::CURP, 'email' => 'ana@example.test', 'expira' => now()->addMinutes(30)->timestamp]]);

        $this->post(route('consulta.solicitudes.comentarios.store', $propia), ['comentario' => 'No debo poder enviar esto'])
            ->assertStatus(422);
        $this->post(route('consulta.solicitudes.comentarios.store', $ajena), ['comentario' => 'No debo ver esta solicitud'])
            ->assertNotFound();
    }

    public function test_document_download_requires_ownership_approval_and_validity(): void
    {
        Storage::fake('local');
        $propia = $this->crearSolicitud('ana@example.test', 'SOL-ANA');
        $ajena = $this->crearSolicitud('otra@example.test', 'SOL-OTRA');
        $documentoPropio = (string) Str::uuid();
        $documentoAjeno = (string) Str::uuid();
        foreach ([[$documentoPropio, $propia], [$documentoAjeno, $ajena]] as [$id, $solicitudId]) {
            DB::table('solicitud_documentos_emitidos')->insert([
                'id' => $id, 'solicitud_id' => $solicitudId,
                'storage_key' => 'emitidos/'.$id.'.pdf', 'nombre_original' => 'constancia.pdf',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            Storage::disk('local')->put('emitidos/'.$id.'.pdf', '%PDF-1.4');
        }
        $this->withSession(['consulta_ciudadana' => ['curp' => self::CURP, 'email' => 'ana@example.test', 'expira' => now()->addMinutes(30)->timestamp]]);

        $this->get(route('consulta.documentos.descargar', $documentoAjeno))->assertNotFound();
        $this->get(route('consulta.documentos.descargar', $documentoPropio))->assertNotFound();
        DB::table('solicitud_estados')->insert(['id' => 2, 'clave' => 'APROBADA', 'nombre' => 'Aprobada']);
        DB::table('solicitudes')->where('id', $propia)->update(['solicitud_estado_id' => 2]);
        $this->get(route('consulta.documentos.descargar', $documentoPropio))->assertOk();
        DB::table('solicitud_documentos_emitidos')->where('id', $documentoPropio)->update(['fecha_expiracion' => now()->subMinute()]);
        $this->get(route('consulta.documentos.descargar', $documentoPropio))->assertStatus(410);
    }

    public function test_payment_reference_download_requires_ownership_and_pending_payment_status(): void
    {
        Storage::fake('local');
        $propia = $this->crearSolicitud('ana@example.test', 'SOL-ANA');
        $ajena = $this->crearSolicitud('otra@example.test', 'SOL-OTRA');
        DB::table('solicitud_estados')->insert(['id' => 2, 'clave' => 'EN_ESPERA_DE_PAGO', 'nombre' => 'En espera de pago']);
        DB::table('solicitudes')->whereIn('id', [$propia, $ajena])->update(['solicitud_estado_id' => 2]);

        $pagoPropio = (string) Str::uuid();
        $pagoAjeno = (string) Str::uuid();
        foreach ([[$pagoPropio, $propia], [$pagoAjeno, $ajena]] as [$id, $solicitudId]) {
            DB::table('solicitud_pagos')->insert([
                'id' => $id, 'solicitud_id' => $solicitudId, 'referencia' => 'NEZA-'.$id,
                'importe' => 100, 'fecha_emision' => now(), 'fecha_vencimiento' => now()->addDays(5),
                'pdf_nombre_original' => 'referencia-pago.pdf', 'pdf_storage_key' => 'pagos/'.$id.'.pdf', 'pdf_hash_archivo' => hash('sha256', $id),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            Storage::disk('local')->put('pagos/'.$id.'.pdf', '%PDF-1.4');
        }
        $this->withSession(['consulta_ciudadana' => ['curp' => self::CURP, 'email' => 'ana@example.test', 'expira' => now()->addMinutes(30)->timestamp]]);

        $this->get(route('consulta.pagos.referencia.descargar', $pagoAjeno))->assertNotFound();
        $this->get(route('consulta.pagos.referencia.descargar', $pagoPropio))->assertOk();
        DB::table('solicitud_estados')->insert(['id' => 3, 'clave' => 'EN_REVISION_FINAL', 'nombre' => 'En revisión final']);
        DB::table('solicitudes')->where('id', $propia)->update(['solicitud_estado_id' => 3]);
        $this->get(route('consulta.pagos.referencia.descargar', $pagoPropio))->assertNotFound();
    }

    public function test_citizen_can_upload_payment_receipt_for_own_pending_payment(): void
    {
        Storage::fake('local');
        $propia = $this->crearSolicitud('ana@example.test', 'SOL-ANA');
        $ajena = $this->crearSolicitud('otra@example.test', 'SOL-OTRA');
        DB::table('solicitud_estados')->insert(['id' => 2, 'clave' => 'EN_ESPERA_DE_PAGO', 'nombre' => 'En espera de pago']);
        DB::table('solicitudes')->whereIn('id', [$propia, $ajena])->update(['solicitud_estado_id' => 2]);

        $pagoPropio = (string) Str::uuid();
        $pagoAjeno = (string) Str::uuid();
        foreach ([[$pagoPropio, $propia], [$pagoAjeno, $ajena]] as [$id, $solicitudId]) {
            DB::table('solicitud_pagos')->insert([
                'id' => $id, 'solicitud_id' => $solicitudId, 'referencia' => 'NEZA-'.$id,
                'importe' => 117.31, 'fecha_emision' => now(), 'fecha_vencimiento' => now()->addDays(30),
                'pdf_nombre_original' => 'referencia-pago.pdf', 'pdf_storage_key' => 'pagos/'.$id.'.pdf', 'pdf_hash_archivo' => hash('sha256', $id),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->withSession(['consulta_ciudadana' => ['curp' => self::CURP, 'email' => 'ana@example.test', 'expira' => now()->addMinutes(30)->timestamp]]);

        $this->get(route('consulta.solicitudes.detalle', $propia))
            ->assertOk()
            ->assertSee('Sube tu comprobante de pago');

        $this->post(route('consulta.pagos.comprobante.subir', $pagoAjeno), [
            'comprobante' => UploadedFile::fake()->create('comprobante.pdf', 100, 'application/pdf'),
        ])->assertNotFound();

        $this->post(route('consulta.pagos.comprobante.subir', $pagoPropio), [
            'comprobante' => UploadedFile::fake()->create('comprobante.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('comprobante');

        $this->post(route('consulta.pagos.comprobante.subir', $pagoPropio), [
            'comprobante' => UploadedFile::fake()->create('comprobante.pdf', 100, 'application/pdf'),
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('solicitud_pagos', [
            'id' => $pagoPropio,
            'comprobante_nombre_original' => 'comprobante.pdf',
        ]);
        Storage::disk('local')->assertExists(
            DB::table('solicitud_pagos')->where('id', $pagoPropio)->value('comprobante_storage_key')
        );

        DB::table('solicitud_pagos')->where('id', $pagoPropio)->update(['fecha_vencimiento' => now()->subDay()]);
        $this->post(route('consulta.pagos.comprobante.subir', $pagoPropio), [
            'comprobante' => UploadedFile::fake()->create('comprobante.pdf', 100, 'application/pdf'),
        ])->assertStatus(422);
    }

    public function test_five_wrong_codes_invalidate_the_token(): void
    {
        $this->crearSolicitud('ana@example.test', 'SOL-ANA');
        $this->post(route('consulta.token.solicitar'), ['curp' => self::CURP, 'email_contacto' => 'ana@example.test']);
        $token = $this->tokenEnviado();
        $equivocado = $token === '000000' ? '111111' : '000000';

        for ($intento = 0; $intento < 5; $intento++) {
            $this->post(route('consulta.token.verificar'), ['token' => $equivocado])->assertSessionHasErrors('token');
        }
        $this->assertDatabaseHas('token_consultas', ['curp' => self::CURP, 'intentos' => 5, 'utilizado' => 1]);
        $this->post(route('consulta.token.verificar'), ['token' => $token])->assertSessionHasErrors('token');
    }

    public function test_resend_waits_one_minute_and_cancel_returns_to_first_step(): void
    {
        $this->crearSolicitud('ana@example.test', 'SOL-ANA');
        $datos = ['curp' => self::CURP, 'email_contacto' => 'ana@example.test'];
        $this->post(route('consulta.token.solicitar'), $datos);
        $this->post(route('consulta.token.solicitar'), $datos)->assertSessionHas('consulta_pendiente');
        Mail::assertSent(TokenConsultaMail::class, 1);
        $this->travel(61)->seconds();
        $this->post(route('consulta.token.solicitar'), $datos);
        Mail::assertSent(TokenConsultaMail::class, 2);
        $this->post(route('consulta.cancelar'))->assertRedirect(route('portal.publico').'#consulta');
        $this->get(route('portal.publico'))->assertOk()->assertSee('Paso 1 de 2');
    }

    public function test_search_and_status_filter_stay_scoped_to_verified_pair(): void
    {
        $propia = $this->crearSolicitud('ana@example.test', 'SOL-ANA');
        $this->crearSolicitud('otra@example.test', 'SOL-OTRA');
        DB::table('solicitud_estados')->insert(['id' => 2, 'clave' => 'APROBADA', 'nombre' => 'Aprobada', 'orden' => 2]);
        DB::table('solicitudes')->where('id', $propia)->update(['solicitud_estado_id' => 2]);
        $this->withSession(['consulta_ciudadana' => ['curp' => self::CURP, 'email' => 'ana@example.test', 'expira' => now()->addMinutes(30)->timestamp]]);

        $this->get(route('consulta.solicitudes', ['q' => 'SOL-ANA', 'estado' => 'APROBADA']))
            ->assertOk()->assertSee('SOL-ANA')->assertDontSee('SOL-OTRA')->assertSee('Limpiar filtros');
        $this->get(route('consulta.solicitudes', ['q' => 'SIN-COINCIDENCIAS']))->assertOk()->assertSee('No encontramos solicitudes');
        $this->get(route('consulta.solicitudes', ['estado' => 'ENVIADA']))->assertSessionHasErrors('estado');
        $this->get(route('consulta.solicitudes', ['estado' => 'RECHAZADA']))->assertSessionHasErrors('estado');
    }

    private function crearSolicitud(string $email, string $folio): string
    {
        $solicitudId = (string) Str::uuid();
        $ahora = now();
        DB::table('solicitudes')->insert([
            'id' => $solicitudId, 'folio' => $folio,
            'tramite_id' => DB::table('tramites')->value('id'),
            'solicitud_estado_id' => DB::table('solicitud_estados')->where('clave', 'ENVIADA')->value('id'),
            'curp_consulta' => self::CURP, 'email_contacto' => $email,
            'nombre_solicitante_snapshot' => 'Ana García', 'fecha_envio' => $ahora,
            'created_at' => $ahora, 'updated_at' => $ahora,
        ]);

        return $solicitudId;
    }

    private function tokenEnviado(): string
    {
        $token = '';
        Mail::assertSent(TokenConsultaMail::class, function (TokenConsultaMail $correo) use (&$token): bool {
            $token = $correo->token;

            return true;
        });

        return $token;
    }

    private function crearEsquemaDeConsulta(): void
    {
        Schema::create('tramites', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->softDeletes();
        });
        Schema::create('solicitud_estados', function (Blueprint $table): void {
            $table->id();
            $table->string('clave');
            $table->string('nombre');
            $table->unsignedInteger('orden')->default(1);
            $table->softDeletes();
        });
        Schema::create('solicitudes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('folio');
            $table->unsignedBigInteger('tramite_id');
            $table->unsignedBigInteger('solicitud_estado_id');
            $table->string('curp_consulta');
            $table->string('email_contacto');
            $table->string('nombre_solicitante_snapshot')->nullable();
            $table->timestamp('fecha_envio')->nullable();
            $table->timestamp('fecha_terminacion')->nullable();
            $table->timestamps();
        });
        Schema::create('token_consultas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('curp');
            $table->string('email_destino');
            $table->string('token_hash');
            $table->timestamp('fecha_expiracion');
            $table->timestamp('fecha_utilizacion')->nullable();
            $table->unsignedInteger('intentos');
            $table->boolean('utilizado');
            $table->timestamps();
        });
        Schema::create('notificacion_estados', function (Blueprint $table): void {
            $table->id();
            $table->string('clave');
        });
        Schema::create('notificacion_emails', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('solicitud_id')->nullable();
            $table->unsignedBigInteger('notificacion_estado_id');
            $table->string('destinatario');
            $table->string('tipo');
            $table->string('asunto');
            $table->unsignedInteger('intentos');
            $table->timestamp('fecha_envio')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
        Schema::create('solicitud_documentos_emitidos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('solicitud_id');
            $table->string('storage_key');
            $table->string('nombre_original');
            $table->timestamp('fecha_expiracion')->nullable();
            $table->timestamps();
        });
        Schema::create('solicitud_pagos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('solicitud_id');
            $table->string('referencia');
            $table->decimal('importe', 12, 2);
            $table->timestamp('fecha_emision');
            $table->timestamp('fecha_vencimiento');
            $table->string('pdf_nombre_original');
            $table->string('pdf_storage_key');
            $table->string('pdf_hash_archivo');
            $table->string('comprobante_nombre_original')->nullable();
            $table->string('comprobante_storage_key')->nullable();
            $table->string('comprobante_mime_type')->nullable();
            $table->unsignedBigInteger('comprobante_tamano_bytes')->nullable();
            $table->string('comprobante_hash_archivo')->nullable();
            $table->timestamp('fecha_comprobante')->nullable();
            $table->timestamps();
        });
        Schema::create('solicitud_historial_estados', function (Blueprint $table): void {
            $table->uuid('solicitud_id');
            $table->unsignedBigInteger('estado_nuevo_id');
            $table->timestamp('fecha');
        });
        Schema::create('solicitud_observaciones', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('solicitud_id');
            $table->text('texto');
            $table->string('tipo')->nullable();
            $table->boolean('visible_ciudadano');
            $table->string('actor_tipo', 30)->nullable();
            $table->string('actor_accesos_id')->nullable();
            $table->string('actor_nombre_snapshot')->nullable();
            $table->uuid('respuesta_a_id')->nullable()->unique();
            $table->timestamps();
        });
        DB::table('tramites')->insert(['id' => 1, 'nombre' => 'Constancia de prueba']);
        DB::table('solicitud_estados')->insert(['id' => 1, 'clave' => 'ENVIADA', 'nombre' => 'Enviada']);
        DB::table('notificacion_estados')->insert([['id' => 1, 'clave' => 'PENDIENTE'], ['id' => 2, 'clave' => 'ENVIADO'], ['id' => 3, 'clave' => 'ERROR']]);
    }
}
