<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\ConfiguracionGeneral;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

            Auditoria::registrar($request, 'CONFIGURACION_GENERAL_ACTUALIZADA', 'configuraciones_generales', $configuracion->id, $anteriores, $configuracion->only(['origen_ficha_pago']));
        });

        return redirect()->route('configuracion-general.edit')->with('success', 'La configuración general se actualizó correctamente.');
    }
}
