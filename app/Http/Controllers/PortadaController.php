<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\ConfiguracionGeneral;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PortadaController extends Controller
{
    public function edit(): View
    {
        return view('admin.portada.edit', ['configuracion' => ConfiguracionGeneral::query()->first()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'portada' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'mimetypes:image/png,image/jpeg,image/webp', 'max:5120'],
        ]);
        $storageKey = $datos['portada']->store('portadas', 'public');
        $anterior = null;

        try {
            DB::transaction(function () use ($request, $storageKey, &$anterior): void {
                $configuracion = ConfiguracionGeneral::query()->firstOrNew();
                $anterior = $configuracion->portada_storage_key;
                $configuracion->fill(['portada_storage_key' => $storageKey]);
                $configuracion->save();
                Auditoria::registrar($request, 'PORTADA_ACTUALIZADA', 'configuraciones_generales', $configuracion->id, $anterior ? ['portada_storage_key' => $anterior] : null, ['portada_storage_key' => $storageKey]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($storageKey);
            throw $exception;
        }

        if ($anterior) {
            Storage::disk('public')->delete($anterior);
        }

        return redirect()->route('portada.edit')->with('success', 'La foto de portada se actualizó correctamente.');
    }
}
