<?php

namespace App\Http\Controllers;

use App\Integrations\AccesosClient;
use App\Models\AreaOperativa;
use App\Models\Auditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AreaOperativaController extends Controller
{
    public function index(AccesosClient $accesos): View
    {
        try {
            $departamentos = collect($accesos->departamentosActivos())
                ->whereNull('parent_id')
                ->values();
        } catch (\Throwable) {
            $departamentos = collect();
        }

        return view('solicitudes.areas-operativas', [
            'areas' => AreaOperativa::query()->orderBy('nombre_snapshot')->get(),
            'departamentos' => $departamentos,
        ]);
    }

    public function store(Request $request, AccesosClient $accesos): RedirectResponse
    {
        $datos = $request->validate(['departamento_accesos_id' => ['required', 'string', 'max:100']]);

        try {
            $departamento = collect($accesos->departamentosActivos())
                ->first(fn (array $item) => $item['id'] === $datos['departamento_accesos_id'] && $item['parent_id'] === null);
        } catch (\Throwable) {
            return back()->withErrors(['departamento_accesos_id' => 'No fue posible consultar el catálogo de Accesos.']);
        }

        if (! $departamento) {
            return back()->withErrors(['departamento_accesos_id' => 'Selecciona un departamento activo y válido de Accesos.']);
        }

        DB::transaction(function () use ($departamento, $request): void {
            $area = AreaOperativa::query()->firstOrNew(['accesos_departamento_id' => $departamento['id']]);
            $anteriores = $area->exists ? $area->only(['nombre_snapshot', 'siglas_snapshot', 'activa']) : null;
            $area->fill(['nombre_snapshot' => $departamento['nombre'], 'siglas_snapshot' => $departamento['siglas'], 'activa' => true]);
            $area->save();
            $this->auditar($request, $area, $anteriores, 'AREA_OPERATIVA_HABILITADA');
        });

        return redirect()->route('solicitudes.areas.index')->with('success', 'El área operativa se habilitó correctamente.');
    }

    public function update(Request $request, AreaOperativa $area): RedirectResponse
    {
        DB::transaction(function () use ($request, $area): void {
            $anteriores = $area->only(['activa']);
            $area->update(['activa' => ! $area->activa]);
            $this->auditar($request, $area, $anteriores, $area->activa ? 'AREA_OPERATIVA_HABILITADA' : 'AREA_OPERATIVA_DESHABILITADA');
        });

        return redirect()->route('solicitudes.areas.index')->with('success', 'El estado del área se actualizó correctamente.');
    }

    private function auditar(Request $request, AreaOperativa $area, ?array $anteriores, string $accion): void
    {
        Auditoria::registrar($request, $accion, 'areas_operativas', $area->id, $anteriores, $area->only(['accesos_departamento_id', 'nombre_snapshot', 'siglas_snapshot', 'activa']));
    }
}
