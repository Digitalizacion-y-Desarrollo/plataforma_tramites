<?php

namespace App\Http\Controllers;

use App\Models\NotificacionEstado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificacionEstadosController extends Controller
{
    public function index()
    {
        $estados = NotificacionEstado::query()->orderBy('orden')->orderBy('nombre')->paginate(10);
        $totalEstados = NotificacionEstado::count();
        $estadosActivos = NotificacionEstado::where('activo', true)->count();

        return view('catalogos.notificacion_estado.index', compact('estados', 'totalEstados', 'estadosActivos'));
    }

    public function create()
    {
        return redirect()->route('catalogo-estados-notificaciones.index');
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        NotificacionEstado::create(['clave' => $this->generarClave(), ...$datos, 'activo' => $request->boolean('activo')]);

        return redirect()->route('catalogo-estados-notificaciones.index')->with('success', 'El estado de notificación se creó correctamente.');
    }

    public function show(NotificacionEstado $catalogo_estado_notificacion)
    {
        return redirect()->route('catalogo-estados-notificaciones.index');
    }

    public function edit(NotificacionEstado $catalogo_estado_notificacion)
    {
        return redirect()->route('catalogo-estados-notificaciones.index');
    }

    public function update(Request $request, NotificacionEstado $catalogo_estado_notificacion)
    {
        $catalogo_estado_notificacion->update([...$this->validar($request), 'activo' => $request->boolean('activo')]);

        return redirect()->route('catalogo-estados-notificaciones.index')->with('success', 'El estado de notificación se actualizó correctamente.');
    }

    public function destroy(NotificacionEstado $catalogo_estado_notificacion)
    {
        if (DB::table('notificacion_emails')->where('notificacion_estado_id', $catalogo_estado_notificacion->id)->exists()) {
            return redirect()->route('catalogo-estados-notificaciones.index')->withErrors(['estado' => 'No se puede eliminar un estado utilizado por notificaciones enviadas.']);
        }

        $catalogo_estado_notificacion->delete();

        return redirect()->route('catalogo-estados-notificaciones.index')->with('success', 'El estado de notificación se eliminó correctamente.');
    }

    public function cambiarEstado(NotificacionEstado $catalogo_estado_notificacion)
    {
        $catalogo_estado_notificacion->update(['activo' => ! $catalogo_estado_notificacion->activo]);

        return redirect()->route('catalogo-estados-notificaciones.index')->with('success', $catalogo_estado_notificacion->activo ? 'El estado de notificación se activó correctamente.' : 'El estado de notificación se deshabilitó correctamente.');
    }

    private function validar(Request $request): array
    {
        return $request->validate(['nombre' => ['required', 'string', 'max:100'], 'orden' => ['required', 'integer', 'min:0'], 'activo' => ['nullable', 'boolean']]);
    }

    private function generarClave(): string
    {
        do {
            $clave = 'ENO-'.Str::upper(Str::random(8));
        } while (NotificacionEstado::withTrashed()->where('clave', $clave)->exists());

        return $clave;
    }
}
