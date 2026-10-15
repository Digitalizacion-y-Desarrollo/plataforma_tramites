<?php

namespace App\Http\Controllers;

use App\Mail\SolicitudEnviadaMail;
use App\Mail\TokenConsultaMail;
use App\Models\Solicitud;
use App\Models\SolicitudDocumentoEmitido;
use App\Models\Tramite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicoController extends Controller
{
    public function index(): View
    {
        $tramites = $this->tramitesPublicados();

        return view('publico.index', compact('tramites'));
    }

    public function tramites(): View
    {
        $tramites = $this->tramitesPublicados();

        return view('publico.tramites.index', compact('tramites'));
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
            'curp_consulta' => ['required', 'string', 'regex:/^[A-Z]{4}[0-9]{6}[A-Z]{6}[A-Z0-9][0-9]$/i'],
            'email_contacto' => ['required', 'email', 'max:200'],
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
            if ($campo->tipo === 'DOMICILIO') {
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
            DB::table('solicitudes')->insert(['id' => $id, 'folio' => $folio, 'tramite_id' => $tramite->id, 'formulario_version_id' => $formularioVersion->id, 'solicitud_estado_id' => $estadoId, 'curp_consulta' => Str::upper($base['curp_consulta']), 'email_contacto' => $base['email_contacto'], 'fecha_envio' => now(), 'created_at' => now(), 'updated_at' => now()]);
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
                'destinatario' => $base['email_contacto'],
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
        $datos = $request->validate(['curp' => ['required', 'string', 'regex:/^[A-Z]{4}[0-9]{6}[A-Z]{6}[A-Z0-9][0-9]$/i']]);
        $curp = Str::upper($datos['curp']);
        $email = Solicitud::query()->where('curp_consulta', $curp)->latest('created_at')->value('email_contacto');

        if (! $email) {
            return back()->withErrors(['curp' => 'No se encontraron solicitudes para la CURP indicada.'])->withInput();
        }

        $token = (string) random_int(100000, 999999);
        DB::table('token_consultas')->where('curp', $curp)->where('utilizado', false)->update(['utilizado' => true, 'updated_at' => now()]);
        DB::table('token_consultas')->insert(['id' => (string) Str::uuid(), 'curp' => $curp, 'email_destino' => $email, 'token_hash' => hash('sha256', $token), 'fecha_expiracion' => now()->addMinutes(15), 'intentos' => 0, 'utilizado' => false, 'created_at' => now(), 'updated_at' => now()]);

        try {
            Mail::to($email)->send(new TokenConsultaMail($token));
        } catch (\Throwable $exception) {
            Log::error('No se pudo enviar el token de consulta.', ['curp' => $curp, 'exception' => $exception]);

            return back()->withErrors(['curp' => 'No fue posible enviar el token. Intenta nuevamente.'])->withInput();
        }

        return redirect(route('portal.publico').'#consulta')->with('consulta_curp', $curp)->with('token_solicitado', true);
    }

    public function verificarToken(Request $request)
    {
        $datos = $request->validate([
            'curp' => ['required', 'string', 'regex:/^[A-Z]{4}[0-9]{6}[A-Z]{6}[A-Z0-9][0-9]$/i'],
            'token' => ['required', 'digits:6'],
        ]);
        $curp = Str::upper($datos['curp']);
        $valido = DB::transaction(function () use ($curp, $datos) {
            $registro = DB::table('token_consultas')->where('curp', $curp)->where('utilizado', false)->latest('created_at')->lockForUpdate()->first();
            if (! $registro || now()->greaterThan($registro->fecha_expiracion) || $registro->intentos >= 5) {
                return false;
            }
            if (! hash_equals($registro->token_hash, hash('sha256', $datos['token']))) {
                $intentos = $registro->intentos + 1;
                DB::table('token_consultas')->where('id', $registro->id)->update(['intentos' => $intentos, 'utilizado' => $intentos >= 5, 'updated_at' => now()]);

                return false;
            }
            DB::table('token_consultas')->where('id', $registro->id)->update(['utilizado' => true, 'fecha_utilizacion' => now(), 'updated_at' => now()]);

            return true;
        });
        if (! $valido) {
            return back()->withErrors(['token' => 'El token es inválido, expiró o alcanzó el límite de intentos.'])->withInput();
        }

        $request->session()->put('consulta_curp_autorizada', $curp);

        return redirect()->route('consulta.solicitudes');
    }

    public function solicitudesConsulta(Request $request): View
    {
        $curp = $request->session()->get('consulta_curp_autorizada');
        abort_unless($curp, 403);
        $solicitudes = Solicitud::query()->where('curp_consulta', $curp)->with(['tramite:id,nombre', 'estado:id,clave,nombre', 'documentoEmitido'])->latest('fecha_envio')->get();

        return view('publico.consulta-solicitudes', compact('solicitudes'));
    }

    public function descargarDocumentoEmitido(Request $request, SolicitudDocumentoEmitido $documento)
    {
        abort_unless($request->session()->get('consulta_curp_autorizada') === $documento->solicitud->curp_consulta, 403);
        abort_unless(Storage::disk('local')->exists($documento->storage_key), 404);
        abort_if($documento->fecha_expiracion !== null && now()->greaterThan($documento->fecha_expiracion), 410, 'El documento ya no está disponible por vencimiento de la descarga.');

        return Storage::disk('local')->download($documento->storage_key, $documento->nombre_original);
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
