<?php

namespace App\Http\Controllers;

use App\Models\Tramite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiTramiteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'departamento_accesos_id' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'in:BORRADOR,PUBLICADO,ARCHIVADO'],
            'activo' => ['nullable', 'boolean'],
            'modalidad' => ['nullable', 'string', 'in:EN_LINEA,PRESENCIAL,MIXTO'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $tramites = Tramite::query()
            ->with('estado:id,clave,nombre')
            ->when($filtros['q'] ?? null, function ($consulta, string $busqueda): void {
                $consulta->where(function ($subconsulta) use ($busqueda): void {
                    $subconsulta->where('clave', 'like', "%{$busqueda}%")
                        ->orWhere('nombre', 'like', "%{$busqueda}%")
                        ->orWhere('descripcion', 'like', "%{$busqueda}%");
                });
            })
            ->when($filtros['departamento_accesos_id'] ?? null, fn ($consulta, string $departamento) => $consulta->where('departamento_accesos_id', $departamento))
            ->when($filtros['estado'] ?? null, fn ($consulta, string $estado) => $consulta->whereHas('estado', fn ($subconsulta) => $subconsulta->where('clave', $estado)))
            ->when(filled($filtros['activo'] ?? null), fn ($consulta) => $consulta->where('activo', $filtros['activo']))
            ->when($filtros['modalidad'] ?? null, function ($consulta, string $modalidad): void {
                if ($modalidad === 'MIXTO') {
                    $consulta->whereRaw("JSON_SEARCH(modalidades, 'one', 'EN_LINEA') IS NOT NULL")
                        ->whereRaw("JSON_SEARCH(modalidades, 'one', 'PRESENCIAL') IS NOT NULL");

                    return;
                }

                $consulta->whereRaw("JSON_SEARCH(modalidades, 'one', ?) IS NOT NULL", [$modalidad]);
            })
            ->orderBy('nombre')
            ->paginate($filtros['per_page'] ?? 20)
            ->withQueryString();

        return response()->json([
            'data' => $tramites->getCollection()->map(fn (Tramite $tramite): array => $this->transformar($tramite))->values(),
            'links' => ['first' => $tramites->url(1), 'last' => $tramites->url($tramites->lastPage()), 'prev' => $tramites->previousPageUrl(), 'next' => $tramites->nextPageUrl()],
            'meta' => ['current_page' => $tramites->currentPage(), 'from' => $tramites->firstItem(), 'last_page' => $tramites->lastPage(), 'per_page' => $tramites->perPage(), 'to' => $tramites->lastItem(), 'total' => $tramites->total()],
        ]);
    }

    private function transformar(Tramite $tramite): array
    {
        return [
            'id' => $tramite->id, 'clave' => $tramite->clave, 'nombre' => $tramite->nombre, 'descripcion' => $tramite->descripcion,
            'doc_a_obtener' => $tramite->doc_a_obtener, 'vigencia_doc' => $tramite->vigencia_doc, 'sujeto_inspeccion' => $tramite->sujeto_inspeccion,
            'criterios_resolucion' => $tramite->criterios_resolucion, 'unidad_administrativa_responsable' => $tramite->unidad_administrativa_responsable,
            'departamento' => ['id' => $tramite->departamento_accesos_id, 'nombre' => $tramite->departamento_nombre_snapshot],
            'estado' => $tramite->estado ? ['id' => $tramite->estado->id, 'clave' => $tramite->estado->clave, 'nombre' => $tramite->estado->nombre] : null,
            'activo' => $tramite->activo, 'tiempo_estimado' => $tramite->tiempo_estimado, 'costo' => $tramite->costo,
            'costo_pago' => $tramite->costo_pago, 'flujo_pago' => $tramite->flujo_pago, 'pago_vigencia_dias' => $tramite->pago_vigencia_dias,
            'modalidades' => $tramite->modalidades, 'requisitos' => $tramite->requisitos, 'proceso' => $tramite->proceso,
            'preguntas_frecuentes' => $tramite->preguntas_frecuentes, 'descarga_expira_dias' => $tramite->descarga_expira_dias,
            'created_at' => $tramite->created_at?->toISOString(), 'updated_at' => $tramite->updated_at?->toISOString(),
        ];
    }
}
