<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionGeneral;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ConfiguracionGeneralController extends Controller
{
    public function edit(): View
    {
        $configuracion = ConfiguracionGeneral::query()->first()
            ?? new ConfiguracionGeneral(['origen_ficha_pago' => 'SISTEMA_EXTERNO']);

        return view('admin.configuracion-general.edit', compact('configuracion'));
    }

    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'origen_ficha_pago' => ['required', 'in:SISTEMA_EXTERNO,GENERADA_EN_PORTAL'],
        ]);
        $configuracion = ConfiguracionGeneral::query()->firstOrNew();
        $anteriores = $configuracion->exists ? $configuracion->only(['origen_ficha_pago']) : null;

        DB::transaction(function () use ($configuracion, $datos, $anteriores, $request): void {
            $configuracion->fill($datos);
            $configuracion->save();

            DB::table('auditorias')->insert([
                'id' => (string) Str::uuid(),
                'actor_accesos_id' => $request->session()->get('accesos.id'),
                'actor_nombre_snapshot' => $request->session()->get('accesos.nombre'),
                'accion' => 'CONFIGURACION_GENERAL_ACTUALIZADA',
                'entidad' => 'configuraciones_generales',
                'entidad_id' => $configuracion->id,
                'datos_anteriores' => $anteriores ? json_encode($anteriores) : null,
                'datos_nuevos' => json_encode($configuracion->only(['origen_ficha_pago'])),
                'ip' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return redirect()->route('configuracion-general.edit')->with('success', 'La configuración general se actualizó correctamente.');
    }
}
