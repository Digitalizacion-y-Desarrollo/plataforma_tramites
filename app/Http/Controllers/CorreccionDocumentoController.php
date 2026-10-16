<?php

namespace App\Http\Controllers;

use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CorreccionDocumentoController extends Controller
{
    public function mostrar(Request $request, string $token): View
    {
        $correccion = $this->buscarCorreccion($token);

        if ($correccion->completada_at) {
            return view('publico.correcciones.completada');
        }

        if ($correccion->bloqueada_at) {
            return view('publico.correcciones.bloqueada');
        }

        if ($request->session()->get('correccion_documentos_id') !== $correccion->id) {
            return view('publico.correcciones.acceso', compact('token'));
        }

        $documentos = $this->documentosDeCorreccion($correccion->id);

        return view('publico.correcciones.formulario', compact('token', 'correccion', 'documentos'));
    }

    public function verificar(Request $request, string $token)
    {
        $datos = $request->validate(['password' => ['required', 'string', 'max:100']]);
        $correccion = DB::transaction(function () use ($token, $datos) {
            $correccion = DB::table('solicitud_correcciones')
                ->where('url_token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            abort_unless($correccion, 404);
            if ($correccion->completada_at || $correccion->bloqueada_at) {
                return null;
            }

            if (! Hash::check($datos['password'], $correccion->password_hash)) {
                $intentos = $correccion->intentos + 1;
                DB::table('solicitud_correcciones')->where('id', $correccion->id)->update([
                    'intentos' => $intentos,
                    'bloqueada_at' => $intentos >= 5 ? now() : null,
                    'updated_at' => now(),
                ]);

                return false;
            }

            return $correccion;
        });

        if (! $correccion) {
            return redirect()->route('correcciones.mostrar', $token)
                ->withErrors(['password' => 'La contraseña es incorrecta o el acceso ya no está disponible.']);
        }

        $request->session()->put('correccion_documentos_id', $correccion->id);

        return redirect()->route('correcciones.mostrar', $token);
    }

    public function actualizar(Request $request, string $token)
    {
        $correccion = $this->buscarCorreccion($token);
        abort_unless($request->session()->get('correccion_documentos_id') === $correccion->id, 403);
        abort_if($correccion->completada_at || $correccion->bloqueada_at, 422, 'El acceso ya no está disponible.');

        $documentos = $this->documentosDeCorreccion($correccion->id);
        $reglas = [];
        foreach ($documentos as $documento) {
            $reglas['archivos.'.$documento->campo_id] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'];
        }
        $request->validate($reglas);

        $archivosGuardados = [];
        try {
            DB::transaction(function () use ($request, $correccion, $documentos, &$archivosGuardados) {
                $correccionBloqueada = DB::table('solicitud_correcciones')->where('id', $correccion->id)->lockForUpdate()->first();
                abort_if(! $correccionBloqueada || $correccionBloqueada->completada_at || $correccionBloqueada->bloqueada_at, 422, 'El acceso ya no está disponible.');

                $solicitud = Solicitud::query()->lockForUpdate()->findOrFail($correccionBloqueada->solicitud_id);
                $estadoCorreccionId = EstadoSolicitud::query()->where('clave', 'CORRECCION_SOLICITADA')->value('id');
                abort_unless($solicitud->solicitud_estado_id === $estadoCorreccionId, 422, 'La solicitud ya no está disponible para corrección.');

                foreach ($documentos as $documento) {
                    $archivo = $request->file('archivos.'.$documento->campo_id);
                    $storageKey = $archivo->store('solicitudes/'.$solicitud->id.'/correcciones/'.$correccionBloqueada->id, 'local');
                    $archivosGuardados[] = $storageKey;
                    $metadata = [
                        'nombre_original' => $archivo->getClientOriginalName(),
                        'storage_key' => $storageKey,
                        'mime_type' => $archivo->getMimeType(),
                        'tamano_bytes' => $archivo->getSize(),
                        'hash_archivo' => hash_file('sha256', $archivo->getRealPath()),
                    ];

                    DB::table('solicitud_correccion_documentos')->where('id', $documento->id)->update([
                        'archivo_corregido' => json_encode($metadata), 'fecha_carga' => now(), 'updated_at' => now(),
                    ]);
                    DB::table('solicitud_respuestas')
                        ->where('solicitud_id', $solicitud->id)
                        ->where('campo_id', $documento->campo_id)
                        ->update(['valor' => json_encode($metadata), 'updated_at' => now()]);
                }

                $enviadaId = EstadoSolicitud::query()->where('clave', 'ENVIADA')->where('activo', true)->value('id');
                abort_unless($enviadaId, 500);
                DB::table('solicitud_correcciones')->where('id', $correccionBloqueada->id)->update(['completada_at' => now(), 'updated_at' => now()]);
                $solicitud->update(['solicitud_estado_id' => $enviadaId, 'fecha_terminacion' => null]);
                DB::table('solicitud_historial_estados')->insert([
                    'id' => (string) Str::uuid(), 'solicitud_id' => $solicitud->id,
                    'estado_anterior_id' => $estadoCorreccionId, 'estado_nuevo_id' => $enviadaId,
                    'comentario' => 'La ciudadanía envió los documentos corregidos.', 'actor_tipo' => 'CIUDADANO', 'fecha' => now(),
                ]);
                DB::table('auditorias')->insert([
                    'id' => (string) Str::uuid(), 'accion' => 'SOLICITUD_DOCUMENTOS_CORREGIDOS',
                    'entidad' => 'SOLICITUD', 'entidad_id' => $solicitud->id,
                    'datos_anteriores' => json_encode(['solicitud_estado_id' => $estadoCorreccionId]),
                    'datos_nuevos' => json_encode(['solicitud_estado_id' => $enviadaId, 'correccion_id' => $correccionBloqueada->id]),
                    'ip' => $request->ip(), 'created_at' => now(),
                ]);
            });
        } catch (\Throwable $exception) {
            foreach ($archivosGuardados as $archivoGuardado) {
                Storage::disk('local')->delete($archivoGuardado);
            }

            throw $exception;
        }

        $request->session()->forget('correccion_documentos_id');

        return redirect()->route('correcciones.mostrar', $token)->with('success', 'Los documentos corregidos se enviaron correctamente.');
    }

    private function buscarCorreccion(string $token): object
    {
        return DB::table('solicitud_correcciones')
            ->where('url_token_hash', hash('sha256', $token))
            ->firstOrFail();
    }

    private function documentosDeCorreccion(string $correccionId)
    {
        return DB::table('solicitud_correccion_documentos as correccion_documento')
            ->join('formulario_campos as campo', 'campo.id', '=', 'correccion_documento.campo_id')
            ->where('correccion_documento.solicitud_correccion_id', $correccionId)
            ->orderBy('campo.orden')
            ->get(['correccion_documento.id', 'correccion_documento.campo_id', 'correccion_documento.motivo', 'campo.etiqueta']);
    }
}
