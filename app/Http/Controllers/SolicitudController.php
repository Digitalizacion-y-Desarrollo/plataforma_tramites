<?php

namespace App\Http\Controllers;

use App\Mail\SolicitudAprobadaMail;
use App\Mail\SolicitudCorreccionDocumentosMail;
use App\Mail\SolicitudRechazadaMail;
use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use App\Models\SolicitudDocumentoEmitido;
use App\Models\Tramite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SolicitudController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'estado_id' => ['nullable', 'integer', 'exists:solicitud_estados,id'],
            'tramite_id' => ['nullable', 'integer', 'exists:tramites,id'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
        ]);

        $consulta = Solicitud::query()
            ->with(['estado:id,clave,nombre', 'tramite:id,clave,nombre,modalidades,descarga_expira_dias'])
            ->when($filtros['q'] ?? null, function ($consulta, string $busqueda): void {
                $consulta->where(function ($consulta) use ($busqueda): void {
                    $consulta->where('folio', 'like', "%{$busqueda}%")
                        ->orWhere('curp_consulta', 'like', "%{$busqueda}%")
                        ->orWhere('email_contacto', 'like', "%{$busqueda}%");
                });
            })
            ->when($filtros['estado_id'] ?? null, fn ($consulta, int $estadoId) => $consulta->where('solicitud_estado_id', $estadoId))
            ->when($filtros['tramite_id'] ?? null, fn ($consulta, int $tramiteId) => $consulta->where('tramite_id', $tramiteId))
            ->when($filtros['fecha_desde'] ?? null, fn ($consulta, string $fecha) => $consulta->whereDate('fecha_envio', '>=', $fecha))
            ->when($filtros['fecha_hasta'] ?? null, fn ($consulta, string $fecha) => $consulta->whereDate('fecha_envio', '<=', $fecha));

        $totalSolicitudes = Solicitud::count();
        $solicitudesEnviadas = Solicitud::whereHas('estado', fn ($consulta) => $consulta->where('clave', 'ENVIADA'))->count();
        $solicitudesAprobadas = Solicitud::whereHas('estado', fn ($consulta) => $consulta->where('clave', 'APROBADA'))->count();
        $solicitudesRechazadas = Solicitud::whereHas('estado', fn ($consulta) => $consulta->where('clave', 'RECHAZADA'))->count();

        $solicitudes = $consulta->orderByDesc('fecha_envio')->orderByDesc('created_at')->paginate(15)->withQueryString();
        $estados = EstadoSolicitud::query()->where('activo', true)->orderBy('orden')->get(['id', 'clave', 'nombre']);
        $tramites = Tramite::query()->orderBy('nombre')->get(['id', 'nombre']);

        return view('solicitudes.index', compact('solicitudes', 'estados', 'tramites', 'totalSolicitudes', 'solicitudesEnviadas', 'solicitudesAprobadas', 'solicitudesRechazadas'));
    }

    public function show(Solicitud $solicitud): View
    {
        $solicitud->load(['estado:id,clave,nombre', 'tramite:id,clave,nombre,modalidades,descarga_expira_dias', 'documentoEmitido']);

        $respuestasPorSeccion = DB::table('solicitud_respuestas as respuesta')
            ->join('formulario_campos as campo', 'campo.id', '=', 'respuesta.campo_id')
            ->join('formulario_secciones as seccion', 'seccion.id', '=', 'campo.seccion_id')
            ->where('respuesta.solicitud_id', $solicitud->id)
            ->orderBy('seccion.orden')
            ->orderBy('campo.orden')
            ->get(['seccion.titulo as seccion_titulo', 'campo.etiqueta as campo_etiqueta', 'respuesta.valor'])
            ->map(function ($respuesta) {
                $respuesta->valor = $this->formatearValor(json_decode($respuesta->valor, true));

                return $respuesta;
            })
            ->groupBy('seccion_titulo');

        $documentos = DB::table('solicitud_documentos as documento')
            ->join('requisito_documentos as requisito', 'requisito.id', '=', 'documento.requisito_documento_id')
            ->join('documento_estados as estado', 'estado.id', '=', 'documento.documento_estado_id')
            ->where('documento.solicitud_id', $solicitud->id)
            ->orderBy('requisito.orden')
            ->get(['documento.id', 'requisito.nombre as requisito_nombre', 'documento.nombre_original', 'documento.tamano_bytes', 'documento.observaciones', 'estado.nombre as estado_nombre'])
            ->map(function ($documento) {
                $documento->origen = 'DOCUMENTO';

                return $documento;
            });

        $archivosDeRespuestas = DB::table('solicitud_respuestas as respuesta')
            ->join('formulario_campos as campo', 'campo.id', '=', 'respuesta.campo_id')
            ->where('respuesta.solicitud_id', $solicitud->id)
            ->where('campo.tipo', 'FILE')
            ->get(['campo.id as campo_id', 'campo.etiqueta as requisito_nombre', 'respuesta.valor'])
            ->map(function ($archivo) {
                $metadata = json_decode($archivo->valor, true) ?: [];

                return (object) [
                    'id' => $archivo->campo_id,
                    'campo_id' => $archivo->campo_id,
                    'requisito_nombre' => $archivo->requisito_nombre,
                    'nombre_original' => $metadata['nombre_original'] ?? 'Archivo sin nombre',
                    'tamano_bytes' => $metadata['tamano_bytes'] ?? 0,
                    'observaciones' => null,
                    'estado_nombre' => 'Enviado',
                    'origen' => 'RESPUESTA',
                    'metadata' => $metadata,
                ];
            })
            ->filter(fn ($archivo) => $archivo->nombre_original !== 'Archivo sin nombre');

        $documentos = $documentos->concat($archivosDeRespuestas);
        $archivosCorregibles = $archivosDeRespuestas->values();

        return view('solicitudes.show', compact('solicitud', 'respuestasPorSeccion', 'documentos', 'archivosCorregibles'));
    }

    public function solicitarCorreccion(Request $request, Solicitud $solicitud)
    {
        $solicitud->load('estado');
        abort_unless($solicitud->estado?->clave === 'ENVIADA', 422, 'Solo pueden solicitarse correcciones de solicitudes enviadas.');

        $datos = $request->validate([
            'documentos' => ['required', 'array', 'min:1'],
            'documentos.*.motivo' => ['required', 'string', 'max:5000'],
        ]);
        $camposIds = array_keys($datos['documentos']);
        $archivos = DB::table('solicitud_respuestas as respuesta')
            ->join('formulario_campos as campo', 'campo.id', '=', 'respuesta.campo_id')
            ->where('respuesta.solicitud_id', $solicitud->id)
            ->where('campo.tipo', 'FILE')
            ->whereIn('campo.id', $camposIds)
            ->get(['campo.id as campo_id', 'campo.etiqueta', 'respuesta.valor']);
        abort_unless($archivos->count() === count($camposIds), 422, 'Selecciona únicamente documentos pertenecientes a esta solicitud.');

        $actor = [
            'id' => $request->session()->get('accesos.id'),
            'nombre' => $request->session()->get('accesos.nombre'),
            'departamento' => $request->session()->get('accesos.departamento'),
        ];
        $urlToken = Str::random(64);
        $password = Str::password(12, true, true, false);
        $documentosCorreo = [];

        $notificacionId = DB::transaction(function () use ($solicitud, $archivos, $datos, $actor, $urlToken, $password, &$documentosCorreo, $request) {
            $solicitudBloqueada = Solicitud::query()->lockForUpdate()->findOrFail($solicitud->id);
            $estadoAnterior = $solicitudBloqueada->solicitud_estado_id;
            $enviadaId = EstadoSolicitud::query()->where('clave', 'ENVIADA')->value('id');
            abort_unless($estadoAnterior === $enviadaId, 422, 'La solicitud ya fue atendida.');
            $estadoCorreccionId = EstadoSolicitud::query()->where('clave', 'CORRECCION_SOLICITADA')->where('activo', true)->value('id');
            $pendienteId = DB::table('notificacion_estados')->where('clave', 'PENDIENTE')->where('activo', true)->value('id');
            abort_unless($estadoCorreccionId && $pendienteId, 500);
            abort_if(DB::table('solicitud_correcciones')->where('solicitud_id', $solicitudBloqueada->id)->whereNull('completada_at')->whereNull('bloqueada_at')->exists(), 422, 'Ya existe una corrección pendiente para esta solicitud.');

            $correccionId = (string) Str::uuid();
            DB::table('solicitud_correcciones')->insert([
                'id' => $correccionId, 'solicitud_id' => $solicitudBloqueada->id,
                'url_token_hash' => hash('sha256', $urlToken), 'password_hash' => Hash::make($password),
                'intentos' => 0, 'actor_accesos_id' => $actor['id'], 'actor_nombre_snapshot' => $actor['nombre'],
                'actor_departamento_snapshot' => $actor['departamento'], 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($archivos as $archivo) {
                $motivo = trim($datos['documentos'][$archivo->campo_id]['motivo']);
                DB::table('solicitud_correccion_documentos')->insert([
                    'id' => (string) Str::uuid(), 'solicitud_correccion_id' => $correccionId, 'campo_id' => $archivo->campo_id,
                    'motivo' => $motivo, 'archivo_original' => $archivo->valor, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $documentosCorreo[] = ['nombre' => $archivo->etiqueta, 'motivo' => $motivo];
            }
            $solicitudBloqueada->update(['solicitud_estado_id' => $estadoCorreccionId]);
            DB::table('solicitud_historial_estados')->insert([
                'id' => (string) Str::uuid(), 'solicitud_id' => $solicitudBloqueada->id,
                'estado_anterior_id' => $estadoAnterior, 'estado_nuevo_id' => $estadoCorreccionId,
                'comentario' => 'Se solicitó la corrección de documentos.', 'actor_tipo' => 'SERVIDOR_PUBLICO',
                'actor_accesos_id' => $actor['id'], 'actor_nombre_snapshot' => $actor['nombre'],
                'actor_departamento_snapshot' => $actor['departamento'], 'fecha' => now(),
            ]);
            DB::table('auditorias')->insert([
                'id' => (string) Str::uuid(), 'actor_accesos_id' => $actor['id'], 'actor_nombre_snapshot' => $actor['nombre'],
                'accion' => 'SOLICITUD_CORRECCION_SOLICITADA', 'entidad' => 'SOLICITUD', 'entidad_id' => $solicitudBloqueada->id,
                'datos_anteriores' => json_encode(['solicitud_estado_id' => $estadoAnterior]),
                'datos_nuevos' => json_encode(['solicitud_estado_id' => $estadoCorreccionId, 'campos_ids' => $archivos->pluck('campo_id')->all()]),
                'ip' => $request->ip(), 'created_at' => now(),
            ]);
            $notificacionId = (string) Str::uuid();
            DB::table('notificacion_emails')->insert([
                'id' => $notificacionId, 'solicitud_id' => $solicitudBloqueada->id, 'notificacion_estado_id' => $pendienteId,
                'destinatario' => $solicitudBloqueada->email_contacto, 'tipo' => 'SOLICITUD_CORRECCION_DOCUMENTOS',
                'asunto' => 'Corrección de documentos requerida · Folio '.$solicitudBloqueada->folio,
                'intentos' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);

            return $notificacionId;
        });

        $solicitud->refresh()->load('tramite');
        $this->enviarSolicitudCorreccion($solicitud, $notificacionId, $documentosCorreo, route('correcciones.mostrar', $urlToken), $password);

        return redirect()->route('solicitudes.show', $solicitud)->with('success', 'La solicitud de corrección se envió a la ciudadanía.');
    }

    public function aprobar(Request $request, Solicitud $solicitud)
    {
        $solicitud->load('tramite', 'estado');
        abort_unless($solicitud->estado?->clave === 'ENVIADA', 422, 'Solo pueden aprobarse solicitudes enviadas.');

        $modalidades = collect($solicitud->tramite?->modalidades ?? [])->unique()->values();
        $esEnLinea = $modalidades->count() === 1 && $modalidades->contains('EN_LINEA');
        $esMixto = $modalidades->contains('EN_LINEA') && $modalidades->contains('PRESENCIAL');
        abort_unless($esEnLinea || $esMixto, 422, 'Este trámite no puede aprobarse desde el portal.');

        $datos = $request->validate($esEnLinea ? [
            'documento_final' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'instrucciones' => ['nullable', 'string', 'max:5000'],
            'descarga_expira_dias' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ] : [
            'instrucciones' => ['required', 'string', 'max:5000'],
        ]);

        $actor = [
            'id' => $request->session()->get('accesos.id'),
            'nombre' => $request->session()->get('accesos.nombre'),
            'departamento' => $request->session()->get('accesos.departamento'),
        ];
        $archivoGuardado = null;

        try {
            [$notificacionId, $documentoEmitido, $instrucciones] = DB::transaction(function () use ($solicitud, $esEnLinea, $datos, $actor, &$archivoGuardado, $request) {
                $solicitudBloqueada = Solicitud::query()->lockForUpdate()->findOrFail($solicitud->id);
                $estadoAnterior = $solicitudBloqueada->solicitud_estado_id;
                $enviadaId = EstadoSolicitud::query()->where('clave', 'ENVIADA')->value('id');
                abort_unless($estadoAnterior === $enviadaId, 422, 'La solicitud ya fue atendida.');
                $aprobadaId = EstadoSolicitud::query()->where('clave', 'APROBADA')->where('activo', true)->value('id');
                $pendienteId = DB::table('notificacion_estados')->where('clave', 'PENDIENTE')->where('activo', true)->value('id');
                abort_unless($aprobadaId && $pendienteId, 500);

                $documentoEmitido = null;
                $instrucciones = null;
                if ($esEnLinea) {
                    $archivo = $datos['documento_final'];
                    $storageKey = $archivo->store('solicitudes/'.$solicitudBloqueada->id.'/emitidos', 'local');
                    $archivoGuardado = $storageKey;
                    $fechaEmision = now();
                    $expiraDias = $datos['descarga_expira_dias'] ?? DB::table('tramites')->where('id', $solicitudBloqueada->tramite_id)->value('descarga_expira_dias');
                    $documentoEmitido = SolicitudDocumentoEmitido::query()->create([
                        'id' => (string) Str::uuid(), 'solicitud_id' => $solicitudBloqueada->id,
                        'nombre_original' => $archivo->getClientOriginalName(), 'storage_key' => $storageKey,
                        'mime_type' => $archivo->getMimeType(), 'tamano_bytes' => $archivo->getSize(),
                        'hash_archivo' => hash_file('sha256', $archivo->getRealPath()),
                        'actor_accesos_id' => $actor['id'], 'actor_nombre_snapshot' => $actor['nombre'], 'fecha_emision' => $fechaEmision,
                        'fecha_expiracion' => $expiraDias ? $fechaEmision->copy()->addDays((int) $expiraDias) : null,
                    ]);
                    $indicaciones = trim($datos['instrucciones'] ?? '');
                    $instrucciones = $indicaciones !== '' ? $indicaciones : null;
                } else {
                    $instrucciones = trim($datos['instrucciones']);
                }

                DB::table('solicitud_aprobaciones')->insert([
                    'id' => (string) Str::uuid(), 'solicitud_id' => $solicitudBloqueada->id,
                    'solicitud_documento_emitido_id' => $documentoEmitido?->id, 'instrucciones' => $instrucciones,
                    'actor_accesos_id' => $actor['id'], 'actor_nombre_snapshot' => $actor['nombre'],
                    'actor_departamento_snapshot' => $actor['departamento'], 'fecha_aprobacion' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
                $solicitudBloqueada->update(['solicitud_estado_id' => $aprobadaId, 'fecha_terminacion' => now()]);
                DB::table('solicitud_historial_estados')->insert([
                    'id' => (string) Str::uuid(), 'solicitud_id' => $solicitudBloqueada->id, 'estado_anterior_id' => $estadoAnterior,
                    'estado_nuevo_id' => $aprobadaId, 'comentario' => $esEnLinea ? ('Solicitud aprobada con documento emitido.'.($instrucciones ? ' Indicaciones: '.$instrucciones : '')) : $instrucciones,
                    'actor_tipo' => 'SERVIDOR_PUBLICO', 'actor_accesos_id' => $actor['id'], 'actor_nombre_snapshot' => $actor['nombre'],
                    'actor_departamento_snapshot' => $actor['departamento'], 'fecha' => now(),
                ]);
                DB::table('auditorias')->insert([
                    'id' => (string) Str::uuid(), 'actor_accesos_id' => $actor['id'], 'actor_nombre_snapshot' => $actor['nombre'],
                    'accion' => 'SOLICITUD_APROBADA', 'entidad' => 'SOLICITUD', 'entidad_id' => $solicitudBloqueada->id,
                    'datos_anteriores' => json_encode(['solicitud_estado_id' => $estadoAnterior]),
                    'datos_nuevos' => json_encode(['solicitud_estado_id' => $aprobadaId, 'documento_emitido_id' => $documentoEmitido?->id, 'instrucciones' => $instrucciones]),
                    'ip' => $request->ip(), 'created_at' => now(),
                ]);
                $notificacionId = (string) Str::uuid();
                DB::table('notificacion_emails')->insert(['id' => $notificacionId, 'solicitud_id' => $solicitudBloqueada->id, 'notificacion_estado_id' => $pendienteId, 'destinatario' => $solicitudBloqueada->email_contacto, 'tipo' => 'SOLICITUD_APROBADA', 'asunto' => 'Solicitud aprobada · Folio '.$solicitudBloqueada->folio, 'intentos' => 0, 'created_at' => now(), 'updated_at' => now()]);

                return [$notificacionId, $documentoEmitido, $instrucciones];
            });
        } catch (\Throwable $exception) {
            if ($archivoGuardado) {
                Storage::disk('local')->delete($archivoGuardado);
            }
            throw $exception;
        }

        $solicitud->refresh()->load('tramite');
        $this->enviarAprobacion($solicitud, $notificacionId, $documentoEmitido, $instrucciones);

        return redirect()->route('solicitudes.show', $solicitud)->with('success', 'La solicitud fue aprobada correctamente.');
    }

    public function rechazar(Request $request, Solicitud $solicitud)
    {
        $solicitud->load('estado');
        abort_unless($solicitud->estado?->clave === 'ENVIADA', 422, 'Solo pueden rechazarse solicitudes enviadas.');

        $datos = $request->validate([
            'motivo' => ['required', 'string', 'max:5000'],
        ]);
        $motivo = trim($datos['motivo']);
        $actor = [
            'id' => $request->session()->get('accesos.id'),
            'nombre' => $request->session()->get('accesos.nombre'),
            'departamento' => $request->session()->get('accesos.departamento'),
        ];

        $notificacionId = DB::transaction(function () use ($solicitud, $motivo, $actor, $request) {
            $solicitudBloqueada = Solicitud::query()->lockForUpdate()->findOrFail($solicitud->id);
            $estadoAnterior = $solicitudBloqueada->solicitud_estado_id;
            $enviadaId = EstadoSolicitud::query()->where('clave', 'ENVIADA')->value('id');
            abort_unless($estadoAnterior === $enviadaId, 422, 'La solicitud ya fue atendida.');

            $rechazadaId = EstadoSolicitud::query()->where('clave', 'RECHAZADA')->where('activo', true)->value('id');
            $pendienteId = DB::table('notificacion_estados')->where('clave', 'PENDIENTE')->where('activo', true)->value('id');
            abort_unless($rechazadaId && $pendienteId, 500);

            $solicitudBloqueada->update(['solicitud_estado_id' => $rechazadaId, 'fecha_terminacion' => now()]);
            DB::table('solicitud_historial_estados')->insert([
                'id' => (string) Str::uuid(), 'solicitud_id' => $solicitudBloqueada->id,
                'estado_anterior_id' => $estadoAnterior, 'estado_nuevo_id' => $rechazadaId, 'comentario' => $motivo,
                'actor_tipo' => 'SERVIDOR_PUBLICO', 'actor_accesos_id' => $actor['id'],
                'actor_nombre_snapshot' => $actor['nombre'], 'actor_departamento_snapshot' => $actor['departamento'],
                'fecha' => now(),
            ]);
            DB::table('auditorias')->insert([
                'id' => (string) Str::uuid(), 'actor_accesos_id' => $actor['id'],
                'actor_nombre_snapshot' => $actor['nombre'], 'accion' => 'SOLICITUD_RECHAZADA',
                'entidad' => 'SOLICITUD', 'entidad_id' => $solicitudBloqueada->id,
                'datos_anteriores' => json_encode(['solicitud_estado_id' => $estadoAnterior]),
                'datos_nuevos' => json_encode(['solicitud_estado_id' => $rechazadaId, 'motivo' => $motivo]),
                'ip' => $request->ip(), 'created_at' => now(),
            ]);

            $notificacionId = (string) Str::uuid();
            DB::table('notificacion_emails')->insert([
                'id' => $notificacionId, 'solicitud_id' => $solicitudBloqueada->id,
                'notificacion_estado_id' => $pendienteId, 'destinatario' => $solicitudBloqueada->email_contacto,
                'tipo' => 'SOLICITUD_RECHAZADA', 'asunto' => 'Solicitud rechazada · Folio '.$solicitudBloqueada->folio,
                'intentos' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);

            return $notificacionId;
        });

        $solicitud->refresh()->load('tramite');
        $this->enviarRechazo($solicitud, $notificacionId, $motivo);

        return redirect()->route('solicitudes.show', $solicitud)->with('success', 'La solicitud fue rechazada correctamente.');
    }

    private function enviarAprobacion(Solicitud $solicitud, string $notificacionId, ?SolicitudDocumentoEmitido $documentoEmitido, ?string $instrucciones): void
    {
        try {
            Mail::to($solicitud->email_contacto)->send(new SolicitudAprobadaMail($solicitud, $documentoEmitido, $instrucciones));
            DB::table('notificacion_emails')->where('id', $notificacionId)->update(['notificacion_estado_id' => DB::table('notificacion_estados')->where('clave', 'ENVIADO')->value('id'), 'intentos' => 1, 'fecha_envio' => now(), 'updated_at' => now()]);
        } catch (\Throwable $exception) {
            DB::table('notificacion_emails')->where('id', $notificacionId)->update(['notificacion_estado_id' => DB::table('notificacion_estados')->where('clave', 'ERROR')->value('id'), 'intentos' => 1, 'error' => Str::limit($exception->getMessage(), 65535, ''), 'updated_at' => now()]);
            Log::error('No se pudo enviar la aprobación de solicitud.', ['solicitud_id' => $solicitud->id, 'exception' => $exception]);
        }
    }

    private function enviarRechazo(Solicitud $solicitud, string $notificacionId, string $motivo): void
    {
        try {
            Mail::to($solicitud->email_contacto)->send(new SolicitudRechazadaMail($solicitud, $motivo));
            DB::table('notificacion_emails')->where('id', $notificacionId)->update([
                'notificacion_estado_id' => DB::table('notificacion_estados')->where('clave', 'ENVIADO')->value('id'),
                'intentos' => 1, 'fecha_envio' => now(), 'updated_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            DB::table('notificacion_emails')->where('id', $notificacionId)->update([
                'notificacion_estado_id' => DB::table('notificacion_estados')->where('clave', 'ERROR')->value('id'),
                'intentos' => 1, 'error' => Str::limit($exception->getMessage(), 65535, ''), 'updated_at' => now(),
            ]);
            Log::error('No se pudo enviar el rechazo de solicitud.', ['solicitud_id' => $solicitud->id, 'exception' => $exception]);
        }
    }

    private function enviarSolicitudCorreccion(Solicitud $solicitud, string $notificacionId, array $documentos, string $url, string $password): void
    {
        try {
            Mail::to($solicitud->email_contacto)->send(new SolicitudCorreccionDocumentosMail($solicitud, $documentos, $url, $password));
            DB::table('notificacion_emails')->where('id', $notificacionId)->update([
                'notificacion_estado_id' => DB::table('notificacion_estados')->where('clave', 'ENVIADO')->value('id'),
                'intentos' => 1, 'fecha_envio' => now(), 'updated_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            DB::table('notificacion_emails')->where('id', $notificacionId)->update([
                'notificacion_estado_id' => DB::table('notificacion_estados')->where('clave', 'ERROR')->value('id'),
                'intentos' => 1, 'error' => Str::limit($exception->getMessage(), 65535, ''), 'updated_at' => now(),
            ]);
            Log::error('No se pudo enviar la solicitud de corrección de documentos.', ['solicitud_id' => $solicitud->id, 'exception' => $exception]);
        }
    }

    public function descargarDocumento(Solicitud $solicitud, string $documento): StreamedResponse
    {
        $registro = DB::table('solicitud_documentos')
            ->where('id', $documento)
            ->where('solicitud_id', $solicitud->id)
            ->firstOrFail();

        abort_unless(Storage::disk('local')->exists($registro->storage_key), 404);

        return Storage::disk('local')->download($registro->storage_key, $registro->nombre_original);
    }

    public function previsualizarDocumento(Solicitud $solicitud, string $documento): StreamedResponse
    {
        $registro = DB::table('solicitud_documentos')
            ->where('id', $documento)
            ->where('solicitud_id', $solicitud->id)
            ->firstOrFail();

        abort_unless(Storage::disk('local')->exists($registro->storage_key), 404);

        return Storage::disk('local')->response($registro->storage_key, $registro->nombre_original, [
            'Content-Disposition' => 'inline; filename="'.$registro->nombre_original.'"',
        ]);
    }

    public function descargarArchivoRespuesta(Solicitud $solicitud, string $campo): StreamedResponse
    {
        $registro = DB::table('solicitud_respuestas')
            ->where('solicitud_id', $solicitud->id)
            ->where('campo_id', $campo)
            ->value('valor');
        $metadata = json_decode($registro ?: '', true) ?: [];

        abort_unless(isset($metadata['storage_key'], $metadata['nombre_original']) && Storage::disk('local')->exists($metadata['storage_key']), 404);

        return Storage::disk('local')->download($metadata['storage_key'], $metadata['nombre_original']);
    }

    public function previsualizarArchivoRespuesta(Solicitud $solicitud, string $campo): StreamedResponse
    {
        $registro = DB::table('solicitud_respuestas')
            ->where('solicitud_id', $solicitud->id)
            ->where('campo_id', $campo)
            ->value('valor');
        $metadata = json_decode($registro ?: '', true) ?: [];

        abort_unless(isset($metadata['storage_key'], $metadata['nombre_original']) && Storage::disk('local')->exists($metadata['storage_key']), 404);

        return Storage::disk('local')->response($metadata['storage_key'], $metadata['nombre_original'], [
            'Content-Disposition' => 'inline; filename="'.$metadata['nombre_original'].'"',
        ]);
    }

    private function formatearValor(mixed $valor): string
    {
        if (is_array($valor)) {
            return collect($valor)
                ->map(fn ($elemento) => is_scalar($elemento) ? (string) $elemento : json_encode($elemento, JSON_UNESCAPED_UNICODE))
                ->implode(', ');
        }

        return filled($valor) ? (string) $valor : 'Sin respuesta';
    }
}
