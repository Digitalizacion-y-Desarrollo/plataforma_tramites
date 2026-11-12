<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiSolicitudController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'area_id' => ['nullable', 'string', 'max:100'],
            'estado_id' => ['nullable', 'integer', 'exists:solicitud_estados,id'],
            'tramite_id' => ['nullable', 'integer', 'exists:tramites,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $solicitudes = Solicitud::query()
            ->with([
                'estado:id,clave,nombre',
                'tramite:id,clave,nombre,departamento_accesos_id,departamento_nombre_snapshot',
            ])
            ->when($filtros['area_id'] ?? null, fn ($consulta, string $areaId) => $consulta->where('departamento_accesos_id_snapshot', $areaId))
            ->when($filtros['estado_id'] ?? null, fn ($consulta, int $estadoId) => $consulta->where('solicitud_estado_id', $estadoId))
            ->when($filtros['tramite_id'] ?? null, fn ($consulta, int $tramiteId) => $consulta->where('tramite_id', $tramiteId))
            ->orderByDesc('fecha_envio')
            ->orderByDesc('created_at')
            ->paginate($filtros['per_page'] ?? 20)
            ->withQueryString();

        return response()->json([
            'data' => $solicitudes->getCollection()->map(fn (Solicitud $solicitud): array => $this->transformarSolicitud($solicitud))->values(),
            'links' => [
                'first' => $solicitudes->url(1),
                'last' => $solicitudes->url($solicitudes->lastPage()),
                'prev' => $solicitudes->previousPageUrl(),
                'next' => $solicitudes->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $solicitudes->currentPage(),
                'from' => $solicitudes->firstItem(),
                'last_page' => $solicitudes->lastPage(),
                'per_page' => $solicitudes->perPage(),
                'to' => $solicitudes->lastItem(),
                'total' => $solicitudes->total(),
            ],
        ]);
    }

    private function transformarSolicitud(Solicitud $solicitud): array
    {
        return [
            'id' => $solicitud->id,
            'folio' => $solicitud->folio,
            'solicitante' => [
                'nombres' => $solicitud->nombres_solicitante,
                'apellido_paterno' => $solicitud->apellido_paterno_solicitante,
                'apellido_materno' => $solicitud->apellido_materno_solicitante,
                'nombre_snapshot' => $solicitud->nombre_solicitante_snapshot,
                'curp' => $solicitud->curp_consulta,
                'email' => $solicitud->email_contacto,
                'telefono' => $solicitud->telefono_contacto,
                'tipo_persona' => $solicitud->tipo_persona,
                'razon_social' => $solicitud->razon_social,
                'calle' => $solicitud->calle,
                'numero_exterior' => $solicitud->numero_exterior,
                'colonia' => $solicitud->colonia,
                'codigo_postal' => $solicitud->codigo_postal,
                'municipio' => (int) $solicitud->municipio_id === 715 ? 'Nezahualcóyotl' : null,
                'regimen_fiscal' => $solicitud->regimenFiscal ? [
                    'clave' => $solicitud->regimenFiscal->clave,
                    'nombre' => $solicitud->regimenFiscal->nombre,
                ] : null,
            ],
            'area' => [
                'id' => $solicitud->departamento_accesos_id_snapshot,
                'nombre' => $solicitud->departamento_nombre_snapshot,
            ],
            'tramite' => $solicitud->tramite ? [
                'id' => $solicitud->tramite->id,
                'clave' => $solicitud->tramite->clave,
                'nombre' => $solicitud->tramite->nombre,
            ] : null,
            'estado' => $solicitud->estado ? [
                'id' => $solicitud->estado->id,
                'clave' => $solicitud->estado->clave,
                'nombre' => $solicitud->estado->nombre,
            ] : null,
            'formulario_version_id' => $solicitud->formulario_version_id,
            'flujo_pago' => $solicitud->flujo_pago,
            'importe_pago' => $solicitud->importe_pago,
            'pago_vigencia_dias' => $solicitud->pago_vigencia_dias,
            'fecha_envio' => $solicitud->fecha_envio?->toISOString(),
            'fecha_terminacion' => $solicitud->fecha_terminacion?->toISOString(),
            'created_at' => $solicitud->created_at?->toISOString(),
            'updated_at' => $solicitud->updated_at?->toISOString(),
        ];
    }
}
