<?php

namespace App\Http\Controllers;

use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use App\Models\Tramite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            ->with(['estado:id,clave,nombre', 'tramite:id,clave,nombre'])
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
        $solicitud->load(['estado:id,clave,nombre', 'tramite:id,clave,nombre']);

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
                    'requisito_nombre' => $archivo->requisito_nombre,
                    'nombre_original' => $metadata['nombre_original'] ?? 'Archivo sin nombre',
                    'tamano_bytes' => $metadata['tamano_bytes'] ?? 0,
                    'observaciones' => null,
                    'estado_nombre' => 'Enviado',
                    'origen' => 'RESPUESTA',
                ];
            })
            ->filter(fn ($archivo) => $archivo->nombre_original !== 'Archivo sin nombre');

        $documentos = $documentos->concat($archivosDeRespuestas);

        return view('solicitudes.show', compact('solicitud', 'respuestasPorSeccion', 'documentos'));
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
