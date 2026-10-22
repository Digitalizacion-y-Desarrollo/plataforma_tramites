<?php

namespace App\Http\Controllers;

use App\Mail\SolicitudEnviadaMail;
use App\Mail\TokenConsultaMail;
use App\Models\CanalAyuda;
use App\Models\EstadoSolicitud;
use App\Models\ServicioInteres;
use App\Models\Solicitud;
use App\Models\SolicitudDocumentoEmitido;
use App\Models\SolicitudPago;
use App\Models\Tramite;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicoController extends Controller
{
    public function index(Request $request): View
    {
        $tramites = $this->tramitesPublicados();
        $servicios = Schema::hasTable('servicios_interes') ? ServicioInteres::query()->orderBy('titulo')->get() : collect();
        $consultaPendiente = $request->session()->get('consulta_pendiente');
        if (! is_array($consultaPendiente) || ! isset($consultaPendiente['curp'], $consultaPendiente['email'], $consultaPendiente['expira']) || now()->timestamp >= $consultaPendiente['expira']) {
            if (is_array($consultaPendiente) && isset($consultaPendiente['expira']) && now()->timestamp >= $consultaPendiente['expira']) {
                $request->session()->flash('consulta_aviso', 'El código venció. Inicia una nueva consulta.');
            }
            $request->session()->forget('consulta_pendiente');
            $consultaPendiente = null;
        }

        return view('publico.index', compact('tramites', 'servicios', 'consultaPendiente'));
    }

    public function tramites(Request $request): View
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'modalidad' => ['nullable', 'array'],
            'modalidad.*' => ['string', 'max:20'],
        ]);
        $tramites = $this->tramitesPublicados()->map(function ($tramite) {
            return (object) [
                'tramite' => $tramite,
                'modalidad' => $this->modalidadEtiqueta($tramite),
            ];
        });

        $modalidadesDisponibles = $tramites->pluck('modalidad')->filter()->unique()->sort()->values();
        $termino = trim($filtros['q'] ?? '');
        if ($termino !== '') {
            $termino = mb_strtolower($termino, 'UTF-8');
            $tramites = $tramites->filter(function ($carta) use ($termino) {
                $tramite = $carta->tramite;

                return str_contains(mb_strtolower($tramite->nombre, 'UTF-8'), $termino)
                    || str_contains(mb_strtolower((string) $tramite->descripcion, 'UTF-8'), $termino);
            });
        }
        $seleccion = array_values(array_intersect($modalidadesDisponibles->all(), $filtros['modalidad'] ?? []));
        if ($seleccion !== []) {
            $tramites = $tramites->filter(fn ($carta) => in_array($carta->modalidad, $seleccion, true))->values();
        }

        return view('publico.tramites.index', [
            'tramites' => $tramites->pluck('tramite'),
            'modalidades' => $modalidadesDisponibles,
            'filtros' => ['q' => $termino, 'modalidad' => $seleccion],
            'total' => $tramites->count(),
            'canalesAyuda' => Schema::hasTable('canales_ayuda') ? CanalAyuda::query()->first() : null,
        ]);
    }

    public function detalle(Tramite $tramite): View
    {
        abort_unless($tramite->activo, 404);

        $formularioVersion = $this->formularioActivo($tramite);

        return view('publico.tramites.detalle', compact('tramite', 'formularioVersion'));
    }

    public function iniciar(Tramite $tramite): View
    {
        abort_unless($tramite->activo, 404);

        $formularioVersion = $this->formularioActivo($tramite);
        abort_unless($formularioVersion, 404);

        return view('publico.tramites.formulario', compact('tramite', 'formularioVersion'));
    }

    public function storeSolicitud(Request $request, Tramite $tramite)
    {
        abort_unless($tramite->activo, 404);
        $formularioVersion = $this->formularioActivo($tramite);
        abort_unless($formularioVersion, 404);
        $base = $request->validate([
            'nombres_solicitante' => ['required', 'string', 'min:2', 'max:100'],
            'apellido_paterno_solicitante' => ['required', 'string', 'min:2', 'max:100'],
            'apellido_materno_solicitante' => ['required', 'string', 'min:2', 'max:100'],
            'curp_consulta' => ['required', 'string', 'regex:/^[A-Z]{4}[0-9]{6}[A-Z]{6}[A-Z0-9][0-9]$/i'],
            'email_contacto' => ['required', 'email', 'max:200'],
            'telefono_contacto' => ['required', 'string', 'regex:/^[0-9+()\-\s]{7,30}$/'],
        ]);

        $solicitudActiva = Solicitud::query()
            ->where('tramite_id', $tramite->id)
            ->where('curp_consulta', Str::upper($base['curp_consulta']))
            ->whereHas('estado', fn ($consulta) => $consulta->whereNotIn('clave', ['APROBADA', 'RECHAZADA']))
            ->exists();

        if ($solicitudActiva) {
            return back()
                ->withInput()
                ->with('solicitud_activa', [
                    'consulta_url' => route('portal.publico').'#consulta',
                ]);
        }

        $campos = $formularioVersion->secciones->where('activo', true)->flatMap(fn ($seccion) => $seccion->campos->where('activo', true))->keyBy('id');
        $respuestas = $request->input('respuestas', []);
        foreach ($campos as $campo) {
            if (! array_key_exists($campo->id, $respuestas) && $request->has($campo->clave)) {
                $respuestas[$campo->id] = $request->input($campo->clave);
            }
        }
        $errores = [];
        foreach ($campos as $campo) {
            $requerido = $this->esVisible($campo, $respuestas) && $this->esRequerido($campo, $respuestas);
            $reglas = [$requerido ? 'required' : 'nullable'];
            $valor = $campo->tipo === 'FILE' ? $request->file('respuestas.'.$campo->id) : ($respuestas[$campo->id] ?? null);
            if ($campo->tipo === 'FILE') {
                $reglas = [$requerido ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'];
            }
            if ($campo->tipo === 'ESTADO') {
                $reglas[] = 'in:'.implode(',', array_keys(config('formulario.entidades_federativas')));
            }
            if (in_array($campo->tipo, ['DOMICILIO', 'DOMICILIO_FUERA_NEZA', 'DOMICILIO_USA'], true)) {
                $reglas[] = 'array';
            }
            if ($campo->tipo === 'EMAIL') {
                $reglas[] = 'email:rfc,dns';
            }
            if ($campo->tipo === 'NUMBER') {
                $reglas[] = 'numeric';
            }
            if ($campo->tipo === 'DATE') {
                $reglas[] = 'date';
            }
            if ($campo->tipo === 'CURP') {
                $reglas[] = 'regex:/^[A-Z]{4}[0-9]{6}[A-Z]{6}[A-Z0-9][0-9]$/i';
            }
            $reglasValor = ['valor' => $reglas];
            if ($campo->tipo === 'MULTISELECT') {
                $reglasValor['valor'] = [$requerido ? 'required' : 'nullable', 'array'];
                $reglasValor['valor.*'] = ['in:'.$campo->opciones->where('activo', true)->pluck('valor')->implode(',')];
            } elseif (in_array($campo->tipo, ['SELECT', 'RADIO'], true)) {
                $reglasValor['valor'][] = 'in:'.$campo->opciones->where('activo', true)->pluck('valor')->implode(',');
            }
            $validacion = validator(['valor' => $valor], $reglasValor);
            if ($validacion->fails()) {
                $errores['respuestas.'.$campo->id] = $validacion->errors()->first('valor');
            }
            if ($campo->tipo === 'DOMICILIO' && is_array($valor) && ! $this->domicilioValido($valor)) {
                $errores['respuestas.'.$campo->id] = 'Selecciona una colonia y una calle válidas de la lista disponible.';
            }
            if ($campo->tipo === 'DOMICILIO_FUERA_NEZA' && is_array($valor)) {
                $valor = array_intersect_key($valor, config('formulario.campos_domicilio_fuera_neza'));
                $respuestas[$campo->id] = $valor;
                $mensajeDomicilio = $this->validarDomicilioFueraNeza($valor, $requerido);
                if ($mensajeDomicilio) {
                    $errores['respuestas.'.$campo->id] = $mensajeDomicilio;
                }
            }
            if ($campo->tipo === 'DOMICILIO_USA' && is_array($valor)) {
                $valor['pais'] = 'US';
                $respuestas[$campo->id] = $valor;
                $mensajeDomicilio = $this->validarDomicilioUsa($valor, $requerido);
                if ($mensajeDomicilio) {
                    $errores['respuestas.'.$campo->id] = $mensajeDomicilio;
                }
            }
        }
        if ($errores) {
            return back()->withErrors($errores)->withInput();
        }

        [$solicitudId, $notificacionId] = DB::transaction(function () use ($tramite, $formularioVersion, $campos, $respuestas, $base) {
            $estadoId = DB::table('solicitud_estados')->where('clave', 'ENVIADA')->where('activo', true)->value('id');
            abort_unless($estadoId, 500);
            $notificacionPendienteId = DB::table('notificacion_estados')->where('clave', 'PENDIENTE')->where('activo', true)->value('id');
            abort_unless($notificacionPendienteId, 500);
            $id = (string) Str::uuid();
            do {
                $folio = 'SOL-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
            } while (DB::table('solicitudes')->where('folio', $folio)->exists());
            $nombres = trim($base['nombres_solicitante']);
            $paterno = trim($base['apellido_paterno_solicitante']);
            $materno = trim($base['apellido_materno_solicitante']);
            DB::table('solicitudes')->insert(['id' => $id, 'folio' => $folio, 'tramite_id' => $tramite->id, 'departamento_accesos_id_snapshot' => $tramite->departamento_accesos_id, 'departamento_nombre_snapshot' => $tramite->departamento_nombre_snapshot, 'formulario_version_id' => $formularioVersion->id, 'solicitud_estado_id' => $estadoId, 'curp_consulta' => Str::upper($base['curp_consulta']), 'email_contacto' => Str::lower(trim($base['email_contacto'])), 'telefono_contacto' => trim($base['telefono_contacto']), 'nombre_solicitante_snapshot' => implode(' ', [$nombres, $paterno, $materno]), 'nombres_solicitante' => $nombres, 'apellido_paterno_solicitante' => $paterno, 'apellido_materno_solicitante' => $materno, 'flujo_pago' => $tramite->flujo_pago ?? 'SIN_PAGO', 'importe_pago' => $tramite->flujo_pago === 'SIN_PAGO' ? null : $tramite->costo_pago, 'pago_vigencia_dias' => $tramite->flujo_pago === 'SIN_PAGO' ? null : $tramite->pago_vigencia_dias, 'fecha_envio' => now(), 'created_at' => now(), 'updated_at' => now()]);
            foreach ($campos as $campo) {
                $valor = $respuestas[$campo->id] ?? null;
                if ($campo->tipo === 'FILE' && request()->hasFile('respuestas.'.$campo->id)) {
                    $archivo = request()->file('respuestas.'.$campo->id);
                    $valor = ['nombre_original' => $archivo->getClientOriginalName(), 'storage_key' => $archivo->store('solicitudes/'.$id, 'local'), 'mime_type' => $archivo->getMimeType(), 'tamano_bytes' => $archivo->getSize()];
                }
                if (filled($valor)) {
                    DB::table('solicitud_respuestas')->insert(['id' => (string) Str::uuid(), 'solicitud_id' => $id, 'campo_id' => $campo->id, 'valor' => json_encode($valor), 'created_at' => now(), 'updated_at' => now()]);
                }
            }
            DB::table('solicitud_historial_estados')->insert(['id' => (string) Str::uuid(), 'solicitud_id' => $id, 'estado_nuevo_id' => $estadoId, 'comentario' => 'Solicitud enviada por la ciudadanía.', 'actor_tipo' => 'CIUDADANO', 'fecha' => now()]);
            $notificacionId = (string) Str::uuid();
            DB::table('notificacion_emails')->insert([
                'id' => $notificacionId,
                'solicitud_id' => $id,
                'notificacion_estado_id' => $notificacionPendienteId,
                'destinatario' => Str::lower(trim($base['email_contacto'])),
                'tipo' => 'SOLICITUD_ENVIADA',
                'asunto' => 'Solicitud enviada correctamente · Folio '.$folio,
                'intentos' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [$id, $notificacionId];
        });
        $solicitud = Solicitud::query()->with('tramite')->findOrFail($solicitudId);
        $this->enviarConfirmacionSolicitud($solicitud, $notificacionId);

        return redirect()->route('portal.tramites.iniciar', $tramite)->with('success', 'Tu solicitud fue enviada. Folio: '.$solicitud->folio);
    }

    public function solicitarToken(Request $request)
    {
        $datos = $request->validate([
            'curp' => ['required', 'string', 'regex:/^[A-Z]{4}[0-9]{6}[A-Z]{6}[A-Z0-9][0-9]$/i'],
            'email_contacto' => ['required', 'email', 'max:200'],
        ]);
        $curp = Str::upper(trim($datos['curp']));
        $email = Str::lower(trim($datos['email_contacto']));
        $clave = 'consulta-token:'.hash('sha256', $curp.'|'.$email);

        if (RateLimiter::tooManyAttempts($clave, 3)) {
            $pendiente = $request->session()->get('consulta_pendiente');
            if (is_array($pendiente) && ($pendiente['curp'] ?? null) === $curp && ($pendiente['email'] ?? null) === $email) {
                return redirect(route('portal.publico').'#consulta')->with('consulta_aviso', 'Alcanzaste el límite de envíos. Espera unos minutos para solicitar otro código.');
            }

            return back()->withErrors(['email_contacto' => 'Espera unos minutos antes de solicitar otro código.'])->withInput();
        }
        RateLimiter::hit($clave, 900);

        $solicitud = Solicitud::query()->where('curp_consulta', $curp)->where('email_contacto', $email)->latest('created_at')->first();
        $aviso = 'Si los datos corresponden a una solicitud, recibirás un código en ese correo. Revisa también la carpeta de spam.';
        $pendiente = $request->session()->get('consulta_pendiente');
        $mismosDatos = is_array($pendiente) && ($pendiente['curp'] ?? null) === $curp && ($pendiente['email'] ?? null) === $email;
        $reciente = DB::table('token_consultas')->where('curp', $curp)->where('email_destino', $email)
            ->where('utilizado', false)->where('created_at', '>', now()->subMinute())->latest('created_at')->first();
        if ($reciente || ($mismosDatos && now()->timestamp < ($pendiente['reenviar_en'] ?? 0))) {
            $request->session()->put('consulta_pendiente', [
                'curp' => $curp, 'email' => $email,
                'expira' => $reciente ? Carbon::parse($reciente->fecha_expiracion)->timestamp : $pendiente['expira'],
                'reenviar_en' => $reciente ? Carbon::parse($reciente->created_at)->addMinute()->timestamp : $pendiente['reenviar_en'],
            ]);

            return redirect(route('portal.publico').'#consulta')->with('consulta_aviso', $aviso);
        }

        $request->session()->put('consulta_pendiente', [
            'curp' => $curp, 'email' => $email,
            'expira' => now()->addMinutes(15)->timestamp,
            'reenviar_en' => now()->addMinute()->timestamp,
        ]);
        if (! $solicitud) {
            return redirect(route('portal.publico').'#consulta')->with('consulta_aviso', $aviso);
        }

        $token = (string) random_int(100000, 999999);
        $tokenId = (string) Str::uuid();
        $notificacionId = (string) Str::uuid();
        DB::transaction(function () use ($curp, $email, $token, $tokenId, $notificacionId, $solicitud): void {
            DB::table('token_consultas')->where('curp', $curp)->where('email_destino', $email)
                ->where('utilizado', false)->update(['utilizado' => true, 'updated_at' => now()]);
            DB::table('token_consultas')->insert([
                'id' => $tokenId, 'curp' => $curp, 'email_destino' => $email,
                'token_hash' => hash_hmac('sha256', $token, (string) config('app.key')),
                'fecha_expiracion' => now()->addMinutes(15), 'intentos' => 0, 'utilizado' => false,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('notificacion_emails')->insert([
                'id' => $notificacionId, 'solicitud_id' => $solicitud->id,
                'notificacion_estado_id' => DB::table('notificacion_estados')->where('clave', 'PENDIENTE')->value('id'),
                'destinatario' => $email, 'tipo' => 'TOKEN_CONSULTA',
                'asunto' => 'Código temporal para consultar tus trámites', 'intentos' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        try {
            Mail::to($email)->send(new TokenConsultaMail($token));
            DB::table('notificacion_emails')->where('id', $notificacionId)->update([
                'notificacion_estado_id' => DB::table('notificacion_estados')->where('clave', 'ENVIADO')->value('id'),
                'intentos' => 1, 'fecha_envio' => now(), 'updated_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            DB::table('token_consultas')->where('id', $tokenId)->update(['utilizado' => true, 'updated_at' => now()]);
            DB::table('notificacion_emails')->where('id', $notificacionId)->update([
                'notificacion_estado_id' => DB::table('notificacion_estados')->where('clave', 'ERROR')->value('id'),
                'intentos' => 1, 'error' => Str::limit($exception->getMessage(), 65535, ''), 'updated_at' => now(),
            ]);
            Log::error('No se pudo enviar el token de consulta.', ['token_consulta_id' => $tokenId, 'exception' => $exception]);

            return redirect(route('portal.publico').'#consulta')->with('consulta_aviso', $aviso);
        }

        return redirect(route('portal.publico').'#consulta')->with('consulta_aviso', $aviso);
    }

    public function verificarToken(Request $request)
    {
        $datos = $request->validate(['token' => ['required', 'digits:6']]);
        $pendiente = $request->session()->get('consulta_pendiente');
        if (! is_array($pendiente) || ! isset($pendiente['curp'], $pendiente['email'], $pendiente['expira']) || now()->timestamp >= $pendiente['expira']) {
            if (is_array($pendiente) && isset($pendiente['curp'], $pendiente['email'])) {
                DB::table('token_consultas')->where('curp', $pendiente['curp'])->where('email_destino', $pendiente['email'])
                    ->where('utilizado', false)->update(['utilizado' => true, 'updated_at' => now()]);
            }
            $request->session()->forget('consulta_pendiente');

            return redirect(route('portal.publico').'#consulta')->with('consulta_aviso', 'El código venció. Inicia una nueva consulta.');
        }
        $valido = DB::transaction(function () use ($pendiente, $datos) {
            $registro = DB::table('token_consultas')->where('curp', $pendiente['curp'])
                ->where('email_destino', $pendiente['email'])->where('utilizado', false)
                ->latest('created_at')->lockForUpdate()->first();
            if (! $registro || now()->greaterThanOrEqualTo($registro->fecha_expiracion) || $registro->intentos >= 5) {
                return false;
            }
            if (! hash_equals($registro->token_hash, hash_hmac('sha256', $datos['token'], (string) config('app.key')))) {
                $intentos = $registro->intentos + 1;
                DB::table('token_consultas')->where('id', $registro->id)->update(['intentos' => $intentos, 'utilizado' => $intentos >= 5, 'updated_at' => now()]);

                return false;
            }
            DB::table('token_consultas')->where('id', $registro->id)->update(['utilizado' => true, 'fecha_utilizacion' => now(), 'updated_at' => now()]);

            return true;
        });
        if (! $valido) {
            return redirect(route('portal.publico').'#consulta')->withErrors(['token' => 'El código es inválido, venció o alcanzó el límite de intentos.']);
        }

        $request->session()->regenerate();
        $request->session()->forget('consulta_pendiente');
        $request->session()->put('consulta_ciudadana', [
            'curp' => $pendiente['curp'], 'email' => $pendiente['email'], 'expira' => now()->addMinutes(30)->timestamp,
        ]);

        return redirect()->route('consulta.solicitudes');
    }

    public function solicitudesConsulta(Request $request): View
    {
        $acceso = $this->accesoCiudadano($request);
        $filtros = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'estado' => ['nullable', 'string', 'max:50']]);
        $filtros['q'] = trim($filtros['q'] ?? '');
        $consulta = Solicitud::query()->where('curp_consulta', $acceso['curp'])->where('email_contacto', $acceso['email']);
        $nombre = (clone $consulta)->whereNotNull('nombre_solicitante_snapshot')->latest('fecha_envio')->value('nombre_solicitante_snapshot') ?: 'Persona solicitante';
        $total = (clone $consulta)->count();
        $estados = EstadoSolicitud::withTrashed()->whereIn('id', (clone $consulta)->select('solicitud_estado_id'))
            ->orderBy('orden')->get(['id', 'clave', 'nombre']);
        validator($filtros, ['estado' => ['nullable', Rule::in($estados->pluck('clave')->all())]])->validate();
        if ($filtros['q'] !== '') {
            $consulta->where(function ($query) use ($filtros) {
                $query->where('folio', 'like', '%'.$filtros['q'].'%')
                    ->orWhereHas('tramite', fn ($tramite) => $tramite->withTrashed()->where('nombre', 'like', '%'.$filtros['q'].'%'));
            });
        }
        if (filled($filtros['estado'] ?? null)) {
            $consulta->where('solicitud_estado_id', $estados->firstWhere('clave', $filtros['estado'])->id);
        }
        $solicitudes = $consulta->with([
            'tramite' => fn ($tramite) => $tramite->withTrashed()->select('id', 'nombre'),
            'estado' => fn ($estado) => $estado->withTrashed()->select('id', 'clave', 'nombre'),
        ])
            ->latest('fecha_envio')->paginate(10)->withQueryString();

        return view('publico.consulta-solicitudes', compact('solicitudes', 'nombre', 'acceso', 'filtros', 'estados', 'total'));
    }

    public function detalleSolicitudConsulta(Request $request, Solicitud $solicitud): View
    {
        $acceso = $this->accesoCiudadano($request);
        abort_unless($solicitud->curp_consulta === $acceso['curp'] && Str::lower($solicitud->email_contacto) === $acceso['email'], 404);
        $solicitud->load([
            'tramite' => fn ($tramite) => $tramite->withTrashed()->select('id', 'nombre'),
            'estado' => fn ($estado) => $estado->withTrashed()->select('id', 'clave', 'nombre'),
            'documentoEmitido',
        ]);
        $pago = null;
        if (Schema::hasTable('solicitud_pagos')) {
            $solicitud->load('pago');
            $pago = $solicitud->pago;
        }
        $historial = DB::table('solicitud_historial_estados as historial')
            ->join('solicitud_estados as estado', 'estado.id', '=', 'historial.estado_nuevo_id')
            ->where('historial.solicitud_id', $solicitud->id)
            ->orderBy('historial.fecha')->get(['estado.nombre as estado', 'historial.fecha']);
        $observaciones = DB::table('solicitud_observaciones')->where('solicitud_id', $solicitud->id)
            ->where('visible_ciudadano', true)->orderBy('created_at')->get(['texto', 'created_at']);

        return view('publico.solicitudes.detalle', compact('solicitud', 'historial', 'observaciones', 'pago'));
    }

    public function cerrarConsulta(Request $request)
    {
        $request->session()->forget(['consulta_ciudadana', 'consulta_pendiente']);
        $request->session()->regenerateToken();

        return redirect(route('portal.publico').'#consulta')->with('consulta_aviso', 'La consulta se cerró correctamente.');
    }

    public function cancelarConsultaPendiente(Request $request)
    {
        $request->session()->forget('consulta_pendiente');

        return redirect(route('portal.publico').'#consulta');
    }

    public function descargarDocumentoEmitido(Request $request, SolicitudDocumentoEmitido $documento)
    {
        $acceso = $this->accesoCiudadano($request);
        abort_unless($acceso['curp'] === $documento->solicitud->curp_consulta && $acceso['email'] === Str::lower($documento->solicitud->email_contacto), 404);
        abort_unless($documento->solicitud->estado?->clave === 'APROBADA', 404);
        abort_unless(Storage::disk('local')->exists($documento->storage_key), 404);
        abort_if($documento->fecha_expiracion !== null && now()->greaterThan($documento->fecha_expiracion), 410, 'El documento ya no está disponible por vencimiento de la descarga.');

        return Storage::disk('local')->download($documento->storage_key, $documento->nombre_original);
    }

    public function descargarReferenciaPago(Request $request, SolicitudPago $pago)
    {
        $acceso = $this->accesoCiudadano($request);
        $pago->load(['solicitud.estado']);

        abort_unless($acceso['curp'] === $pago->solicitud->curp_consulta && $acceso['email'] === Str::lower($pago->solicitud->email_contacto), 404);
        abort_unless($pago->solicitud->estado?->clave === 'EN_ESPERA_DE_PAGO', 404);
        abort_unless(Storage::disk('local')->exists($pago->pdf_storage_key), 404);

        return Storage::disk('local')->download($pago->pdf_storage_key, $pago->pdf_nombre_original);
    }

    private function accesoCiudadano(Request $request): array
    {
        $acceso = $request->session()->get('consulta_ciudadana');
        if (! is_array($acceso) || ! isset($acceso['curp'], $acceso['email'], $acceso['expira']) || now()->timestamp >= $acceso['expira']) {
            $request->session()->forget('consulta_ciudadana');
            throw new HttpResponseException(redirect(route('portal.publico').'#consulta')->with('consulta_aviso', 'Tu acceso temporal terminó. Inicia una nueva consulta.'));
        }

        return $acceso;
    }

    private function modalidadEtiqueta(Tramite $tramite): string
    {
        return match (collect($tramite->modalidades ?? [])->unique()->values()->count()) {
            0 => '',
            1 => collect($tramite->modalidades ?? [])->first() === 'EN_LINEA' ? 'En línea' : 'Presencial',
            default => 'Mixto',
        };
    }

    private function validarDomicilioFueraNeza(array $domicilio, bool $requerido): ?string
    {
        $reglas = ['valor' => [$requerido ? 'required' : 'nullable', 'array']];
        $mensajes = [
            'valor.required' => 'Captura el domicilio completo.',
            'valor.array' => 'El domicilio debe enviarse como un arreglo de datos.',
        ];

        foreach (config('formulario.campos_domicilio_fuera_neza') as $clave => $subcampo) {
            $reglas['valor.'.$clave] = [$requerido ? 'required' : 'nullable', 'string', 'max:'.$subcampo['maxlength']];
            $mensajes['valor.'.$clave.'.required'] = 'Ingresa '.mb_strtolower($subcampo['etiqueta'], 'UTF-8').'.';
            $mensajes['valor.'.$clave.'.max'] = 'El campo '.$subcampo['etiqueta'].' no debe exceder '.$subcampo['maxlength'].' caracteres.';
        }
        $reglas['valor.codigo_postal'] = [$requerido ? 'required' : 'nullable', 'string', 'regex:/^[0-9]{5}$/'];
        $mensajes['valor.codigo_postal.regex'] = 'El código postal debe tener exactamente cinco dígitos.';

        $validacion = validator(['valor' => $domicilio], $reglas, $mensajes);

        return $validacion->fails() ? $validacion->errors()->first() : null;
    }

    private function validarDomicilioUsa(array $domicilio, bool $requerido): ?string
    {
        $reglas = ['valor' => [$requerido ? 'required' : 'nullable', 'array']];
        $mensajes = [
            'valor.required' => 'Captura el domicilio completo.',
            'valor.array' => 'El domicilio debe enviarse como un arreglo de datos.',
        ];

        foreach (config('formulario.campos_domicilio_usa') as $clave => $subcampo) {
            $requeridoSubcampo = $requerido && ($subcampo['requerido'] ?? true);
            $regla = [$requeridoSubcampo ? 'required' : 'nullable', 'string'];
            $mensajes['valor.'.$clave.'.required'] = 'Ingresa '.mb_strtolower($subcampo['etiqueta'], 'UTF-8').'.';

            if ($clave === 'estado') {
                $regla[] = Rule::in(array_keys(config('formulario.estados_usa')));
                $mensajes['valor.estado.in'] = 'Selecciona un estado válido de Estados Unidos.';
            } elseif ($clave === 'codigo_postal') {
                $regla[] = 'regex:/^[0-9]{5}(-[0-9]{4})?$/';
                $mensajes['valor.codigo_postal.regex'] = 'El código postal debe tener cinco dígitos o el formato ZIP+4.';
            } else {
                $regla[] = 'max:'.$subcampo['maxlength'];
                $mensajes['valor.'.$clave.'.max'] = 'El campo '.$subcampo['etiqueta'].' no debe exceder '.$subcampo['maxlength'].' caracteres.';
            }

            $reglas['valor.'.$clave] = $regla;
        }
        $reglas['valor.pais'] = ['required', 'in:US'];
        $mensajes['valor.pais.required'] = 'El país es obligatorio.';
        $mensajes['valor.pais.in'] = 'El país debe ser Estados Unidos.';

        $validacion = validator(['valor' => $domicilio], $reglas, $mensajes);

        return $validacion->fails() ? $validacion->errors()->first() : null;
    }

    private function domicilioValido(array $domicilio): bool
    {
        $coloniaIngresada = mb_strtoupper(trim((string) ($domicilio['colonia'] ?? '')), 'UTF-8');
        $calleIngresada = mb_strtoupper(trim((string) ($domicilio['calle'] ?? '')), 'UTF-8');
        $codigoPostal = trim((string) ($domicilio['codigo_postal'] ?? ''));
        if ($coloniaIngresada === '' || $calleIngresada === '' || ! preg_match('/^\d{5}$/', $codigoPostal)) {
            return false;
        }

        $rutaCapa = public_path('data/capa.json');
        $capa = cache()->rememberForever('domicilios.capa.'.(is_file($rutaCapa) ? md5_file($rutaCapa) : 'ausente'), function () use ($rutaCapa): array {
            $contenido = @file_get_contents($rutaCapa);
            $datos = $contenido ? json_decode($contenido, true) : null;

            return is_array($datos['porColonia'] ?? null) ? $datos['porColonia'] : [];
        });
        $nombreColonia = collect(array_keys($capa))->first(fn (string $nombre) => mb_strtoupper(trim($nombre), 'UTF-8') === $coloniaIngresada);
        if (! $nombreColonia) {
            return false;
        }

        $datosColonia = $capa[$nombreColonia];
        $calleValida = collect($datosColonia['calles'] ?? [])->contains(fn (string $calle) => mb_strtoupper(trim($calle), 'UTF-8') === $calleIngresada);

        return $calleValida && in_array($codigoPostal, $datosColonia['codigosPostales'] ?? [], true);
    }

    private function formularioActivo(Tramite $tramite)
    {
        return $tramite->formulariosVersiones()
            ->wherePivot('activo', true)
            ->whereHas('formulario')
            ->with(['formulario', 'secciones.campos.opciones', 'secciones.campos.gruposCampos.condicionesDestino.campoOrigen.opciones', 'secciones.campos.condicionesDestino.campoOrigen.opciones'])
            ->orderByDesc('numero_version')
            ->first();
    }

    private function tramitesPublicados()
    {
        if (! Schema::hasTable('tramite_estados')) {
            return collect();
        }

        return Tramite::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
    }

    private function esRequerido($campo, array $respuestas): bool
    {
        $requerido = $campo->requerido;
        foreach ($this->condiciones($campo) as $condicion) {
            $valor = $respuestas[$condicion->campo_origen_id] ?? null;
            $coincide = $this->coincideCondicion($condicion, $valor);
            if ($coincide && $condicion->accion === 'REQUIRE') {
                $requerido = true;
            }
            if ($coincide && $condicion->accion === 'OPTIONAL') {
                $requerido = false;
            }
        }

        return $requerido;
    }

    private function esVisible($campo, array $respuestas): bool
    {
        $mostrar = [];
        $ocultar = [];
        foreach ($this->condiciones($campo) as $condicion) {
            $coincide = $this->coincideCondicion($condicion, $respuestas[$condicion->campo_origen_id] ?? null);
            if ($condicion->accion === 'SHOW') {
                $mostrar[] = $coincide;
            }
            if ($condicion->accion === 'HIDE') {
                $ocultar[] = $coincide;
            }
        }

        return ($mostrar === [] || ! in_array(false, $mostrar, true)) && ! in_array(true, $ocultar, true);
    }

    private function coincideCondicion($condicion, mixed $valor): bool
    {
        $comparacion = data_get($condicion->valor_comparacion, 'valor');
        $valorVisible = $condicion->campoOrigen?->opciones->firstWhere('valor', $valor)?->etiqueta ?? $valor;

        return match ($condicion->operador) {
            'EQUALS' => (string) $valorVisible === (string) $comparacion,
            'NOT_EQUALS' => (string) $valorVisible !== (string) $comparacion,
            'CONTAINS' => str_contains((string) $valorVisible, (string) $comparacion),
            'GREATER_THAN' => (float) $valorVisible > (float) $comparacion,
            'LESS_THAN' => (float) $valorVisible < (float) $comparacion,
            'IS_EMPTY' => blank($valorVisible), 'IS_NOT_EMPTY' => filled($valorVisible), default => false,
        };
    }

    private function condiciones($campo): array
    {
        return array_merge(
            $campo->condicionesDestino->all(),
            $campo->gruposCampos->flatMap(fn ($grupo) => $grupo->condicionesDestino)->all(),
        );
    }

    private function enviarConfirmacionSolicitud(Solicitud $solicitud, string $notificacionId): void
    {
        try {
            Mail::to($solicitud->email_contacto)->send(new SolicitudEnviadaMail($solicitud));

            DB::table('notificacion_emails')
                ->where('id', $notificacionId)
                ->update([
                    'notificacion_estado_id' => DB::table('notificacion_estados')->where('clave', 'ENVIADO')->value('id'),
                    'intentos' => 1,
                    'fecha_envio' => now(),
                    'updated_at' => now(),
                ]);
        } catch (\Throwable $exception) {
            DB::table('notificacion_emails')
                ->where('id', $notificacionId)
                ->update([
                    'notificacion_estado_id' => DB::table('notificacion_estados')->where('clave', 'ERROR')->value('id'),
                    'intentos' => 1,
                    'error' => Str::limit($exception->getMessage(), 65535, ''),
                    'updated_at' => now(),
                ]);

            Log::error('No se pudo enviar la confirmación de solicitud.', [
                'solicitud_id' => $solicitud->id,
                'notificacion_id' => $notificacionId,
                'exception' => $exception,
            ]);
        }
    }
}
