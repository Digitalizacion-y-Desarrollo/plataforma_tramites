<?php

namespace App\Http\Controllers;

use App\Models\Tramite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApiRetysTramiteController extends Controller
{
    public function index(): JsonResponse
    {
        $tramites = Tramite::query()
            ->orderBy('nombre')
            ->get(['clave', 'nombre']);

        return response()->json([
            'data' => $tramites->map(fn (Tramite $tramite): array => [
                'clave' => $tramite->clave,
                'nombre' => $tramite->nombre,
            ])->values(),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'clave' => ['nullable', 'string', 'max:50', 'required_without:nombre'],
            'nombre' => ['nullable', 'string', 'max:200', 'required_without:clave'],
        ], [
            'clave.required_without' => 'Indica la clave o el nombre del trámite.',
            'nombre.required_without' => 'Indica la clave o el nombre del trámite.',
        ]);

        if (filled($filtros['clave'] ?? null) && filled($filtros['nombre'] ?? null)) {
            throw ValidationException::withMessages([
                'clave' => 'Consulta el trámite mediante clave o nombre, no ambos.',
                'nombre' => 'Consulta el trámite mediante clave o nombre, no ambos.',
            ]);
        }

        $tramites = Tramite::query()
            ->when($filtros['clave'] ?? null, fn ($consulta, string $clave) => $consulta->where('clave', $clave))
            ->when($filtros['nombre'] ?? null, fn ($consulta, string $nombre) => $consulta->where('nombre', 'like', "%{$nombre}%"))
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'data' => $tramites->map(fn (Tramite $tramite): array => $this->transformar($tramite))->values(),
        ]);
    }

    private function transformar(Tramite $tramite): array
    {
        return [
            'clave' => $tramite->clave,
            'nombre' => $tramite->nombre,
            'area' => $tramite->departamento_nombre_snapshot,
            'tipo' => $tramite->tipo,
            'fundamento_juridico' => $tramite->fund_juridico,
            'costo' => $tramite->costo,
            'requisitos' => $tramite->requisitos,
            'tiempo' => $tramite->tiempo_estimado,
        ];
    }
}
