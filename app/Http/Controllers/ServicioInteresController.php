<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\ServicioInteres;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ServicioInteresController extends Controller
{
    public function index(): View
    {
        $servicios = ServicioInteres::query()->orderBy('titulo')->paginate(12);

        return view('admin.servicios-interes.index', compact('servicios'));
    }

    public function create(): View
    {
        return view('admin.servicios-interes.form', ['servicio' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request, true);
        $logo = $request->file('logo')->store('servicios-interes', 'public');

        try {
            DB::transaction(function () use ($datos, $logo, $request): void {
                $servicio = ServicioInteres::create([
                    'titulo' => $datos['titulo'],
                    'descripcion' => $datos['descripcion'],
                    'enlace' => $datos['enlace'],
                    'logo_storage_key' => $logo,
                ]);
                $this->registrarAuditoria($request, 'SERVICIO_INTERES_CREADO', $servicio, null, $servicio->only(['titulo', 'descripcion', 'enlace', 'logo_storage_key']));
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($logo);
            throw $exception;
        }

        return redirect()->route('servicios-interes.index')->with('success', 'El servicio se creó correctamente.');
    }

    public function edit(ServicioInteres $servicio_interes): View
    {
        return view('admin.servicios-interes.form', ['servicio' => $servicio_interes]);
    }

    public function update(Request $request, ServicioInteres $servicio_interes): RedirectResponse
    {
        $datos = $this->validar($request, false);
        $logoAnterior = $servicio_interes->logo_storage_key;
        $logoNuevo = $request->file('logo')?->store('servicios-interes', 'public');
        $anteriores = $servicio_interes->only(['titulo', 'descripcion', 'enlace', 'logo_storage_key']);

        try {
            DB::transaction(function () use ($datos, $logoNuevo, $servicio_interes, $anteriores, $request): void {
                $servicio_interes->update([
                    'titulo' => $datos['titulo'],
                    'descripcion' => $datos['descripcion'],
                    'enlace' => $datos['enlace'],
                    'logo_storage_key' => $logoNuevo ?? $servicio_interes->logo_storage_key,
                ]);
                $this->registrarAuditoria($request, 'SERVICIO_INTERES_ACTUALIZADO', $servicio_interes, $anteriores, $servicio_interes->only(['titulo', 'descripcion', 'enlace', 'logo_storage_key']));
            });
        } catch (\Throwable $exception) {
            if ($logoNuevo) {
                Storage::disk('public')->delete($logoNuevo);
            }
            throw $exception;
        }

        if ($logoNuevo) {
            Storage::disk('public')->delete($logoAnterior);
        }

        return redirect()->route('servicios-interes.index')->with('success', 'El servicio se actualizó correctamente.');
    }

    public function destroy(Request $request, ServicioInteres $servicio_interes): RedirectResponse
    {
        $anteriores = $servicio_interes->only(['titulo', 'descripcion', 'enlace', 'logo_storage_key']);
        DB::transaction(function () use ($request, $servicio_interes, $anteriores): void {
            $this->registrarAuditoria($request, 'SERVICIO_INTERES_ELIMINADO', $servicio_interes, $anteriores, null);
            $servicio_interes->delete();
        });
        Storage::disk('public')->delete($anteriores['logo_storage_key']);

        return redirect()->route('servicios-interes.index')->with('success', 'El servicio se eliminó correctamente.');
    }

    private function validar(Request $request, bool $creando): array
    {
        return $request->validate([
            'titulo' => ['required', 'string', 'max:160'],
            'descripcion' => ['required', 'string', 'max:500'],
            'enlace' => ['required', 'url:http,https', 'max:2048'],
            'logo' => [$creando ? 'required' : 'nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'mimetypes:image/png,image/jpeg,image/webp', 'max:2048'],
        ]);
    }

    private function registrarAuditoria(Request $request, string $accion, ServicioInteres $servicio, ?array $anteriores, ?array $nuevos): void
    {
        Auditoria::registrar($request, $accion, 'servicios_interes', $servicio->id, $anteriores, $nuevos);
    }
}
