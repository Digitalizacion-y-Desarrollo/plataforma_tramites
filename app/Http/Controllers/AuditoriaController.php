<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditoriaController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'actor' => ['nullable', 'string', 'max:100'],
            'accion' => ['nullable', 'string', 'max:100'],
            'entidad' => ['nullable', 'string', 'max:100'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $auditorias = Auditoria::query()
            ->when($filtros['actor'] ?? null, function ($query, string $actor): void {
                $query->where(function ($subconsulta) use ($actor): void {
                    $subconsulta->where('actor_nombre_snapshot', 'like', '%'.$actor.'%')
                        ->orWhere('actor_accesos_id', $actor);
                });
            })
            ->when($filtros['accion'] ?? null, fn ($query, string $accion) => $query->where('accion', $accion))
            ->when($filtros['entidad'] ?? null, fn ($query, string $entidad) => $query->where('entidad', $entidad))
            ->when($filtros['desde'] ?? null, fn ($query, string $desde) => $query->whereDate('created_at', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn ($query, string $hasta) => $query->whereDate('created_at', '<=', $hasta))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.auditoria.index', [
            'auditorias' => $auditorias,
            'acciones' => Auditoria::query()->distinct()->orderBy('accion')->pluck('accion'),
            'entidades' => Auditoria::query()->distinct()->orderBy('entidad')->pluck('entidad'),
            'filtros' => $filtros,
        ]);
    }
}
