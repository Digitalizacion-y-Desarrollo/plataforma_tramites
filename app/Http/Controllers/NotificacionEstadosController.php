<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
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
        $estado = NotificacionEstado::create(['clave' => $this->generarClave(), ...$datos, 'activo' => $request->boolean('activo')]);
        Auditoria::registrar($request, 'ESTADO_NOTIFICACION_CREADO', 'ESTADO_NOTIFICACION', (string) $estado->id, null, $estado->only(['clave', 'nombre', 'orden', 'activo']));

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
        $anteriores = $catalogo_estado_notificacion->only(['clave', 'nombre', 'orden', 'activo']);
        $catalogo_estado_notificacion->update([...$this->validar($request), 'activo' => $request->boolean('activo')]);
        Auditoria::registrar($request, 'ESTADO_NOTIFICACION_ACTUALIZADO', 'ESTADO_NOTIFICACION', (string) $catalogo_estado_notificacion->id, $anteriores, $catalogo_estado_notificacion->only(['clave', 'nombre', 'orden', 'activo']));

        return redirect()->route('catalogo-estados-notificaciones.index')->with('success', 'El estado de notificación se actualizó correctamente.');
    }

    public function destroy(Request $request, NotificacionEstado $catalogo_estado_notificacion)
    {
        if (DB::table('notificacion_emails')->where('notificacion_estado_id', $catalogo_estado_notificacion->id)->exists()) {
            return redirect()->route('catalogo-estados-notificaciones.index')->withErrors(['estado' => 'No se puede eliminar un estado utilizado por notificaciones enviadas.']);
        }

        DB::transaction(function () use ($request, $catalogo_estado_notificacion): void {
            $anteriores = $catalogo_estado_notificacion->only(['clave', 'nombre', 'orden', 'activo']);
            $catalogo_estado_notificacion->delete();
            Auditoria::registrar($request, 'ESTADO_NOTIFICACION_ELIMINADO', 'ESTADO_NOTIFICACION', (string) $catalogo_estado_notificacion->id, $anteriores, null);
        });

        return redirect()->route('catalogo-estados-notificaciones.index')->with('success', 'El estado de notificación se eliminó correctamente.');
    }

    public function cambiarEstado(Request $request, NotificacionEstado $catalogo_estado_notificacion)
    {
        $anterior = $catalogo_estado_notificacion->activo;
        $catalogo_estado_notificacion->update(['activo' => ! $catalogo_estado_notificacion->activo]);
        Auditoria::registrar($request, $catalogo_estado_notificacion->activo ? 'ESTADO_NOTIFICACION_ACTIVADO' : 'ESTADO_NOTIFICACION_DESACTIVADO', 'ESTADO_NOTIFICACION', (string) $catalogo_estado_notificacion->id, ['activo' => $anterior], ['activo' => $catalogo_estado_notificacion->activo]);

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
