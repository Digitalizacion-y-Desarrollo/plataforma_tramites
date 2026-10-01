<?php

namespace App\Http\Controllers;

use App\Models\EstadoSolicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EstadoSolicitudesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $estados = EstadoSolicitud::query()->orderBy('orden')->orderBy('nombre')->paginate(10);
        $totalEstados = EstadoSolicitud::count();
        $estadosActivos = EstadoSolicitud::where('activo', true)->count();
        $estadosFinales = EstadoSolicitud::where('es_final', true)->count();

        return view('catalogos.solicitud_estado.index', compact('estados', 'totalEstados', 'estadosActivos', 'estadosFinales'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->route('catalogo-estados.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $datos = $this->validar($request);

        EstadoSolicitud::create([
            'clave' => $this->generarClave(),
            ...$datos,
            'activo' => $request->boolean('activo'),
            'es_final' => $request->boolean('es_final'),
        ]);

        return redirect()->route('catalogo-estados.index')->with('success', 'El estado de solicitud se creó correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(EstadoSolicitud $catalogo_estado)
    {
        return redirect()->route('catalogo-estados.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EstadoSolicitud $catalogo_estado)
    {
        return redirect()->route('catalogo-estados.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EstadoSolicitud $catalogo_estado)
    {
        $datos = $this->validar($request);

        $catalogo_estado->update([
            ...$datos,
            'activo' => $request->boolean('activo'),
            'es_final' => $request->boolean('es_final'),
        ]);

        return redirect()->route('catalogo-estados.index')->with('success', 'El estado de solicitud se actualizó correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EstadoSolicitud $catalogo_estado)
    {
        $enUso = DB::table('solicitudes')->where('solicitud_estado_id', $catalogo_estado->id)->exists()
            || DB::table('solicitud_historial_estados')
                ->where('estado_anterior_id', $catalogo_estado->id)
                ->orWhere('estado_nuevo_id', $catalogo_estado->id)
                ->exists();

        if ($enUso) {
            return redirect()->route('catalogo-estados.index')->withErrors([
                'estado' => 'No se puede eliminar un estado que ya forma parte de solicitudes o de su historial.',
            ]);
        }

        $catalogo_estado->delete();

        return redirect()->route('catalogo-estados.index')->with('success', 'El estado de solicitud se eliminó correctamente.');
    }

    public function cambiarEstado(EstadoSolicitud $catalogo_estado)
    {
        $catalogo_estado->update(['activo' => ! $catalogo_estado->activo]);

        return redirect()->route('catalogo-estados.index')->with(
            'success',
            $catalogo_estado->activo ? 'El estado de solicitud se activó correctamente.' : 'El estado de solicitud se deshabilitó correctamente.'
        );
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'orden' => ['required', 'integer', 'min:0'],
            'activo' => ['nullable', 'boolean'],
            'es_final' => ['nullable', 'boolean'],
        ]);
    }

    private function generarClave(): string
    {
        do {
            $clave = 'EST-'.Str::upper(Str::random(8));
        } while (EstadoSolicitud::withTrashed()->where('clave', $clave)->exists());

        return $clave;
    }
}
