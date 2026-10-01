<?php

namespace App\Http\Controllers;

use App\Models\Tramite;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TramiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tramites = Tramite::query()
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->paginate(10);
        $totalTramites = Tramite::count();
        $tramitesActivos = Tramite::where('activo', true)->count();
        $tramitesInactivos = $totalTramites - $tramitesActivos;

        return view('tramites.index', compact('tramites', 'totalTramites', 'tramitesActivos', 'tramitesInactivos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->route('catalogo-tramites.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
        ]);

        Tramite::create([
            'clave' => $this->generarClave(),
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'activo' => $request->boolean('activo'),
        ]);

        return redirect()->route('catalogo-tramites.index')->with('success', 'El trámite se creó correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Tramite $catalogo_tramite)
    {
        return redirect()->route('catalogo-tramites.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tramite $catalogo_tramite)
    {
        return redirect()->route('catalogo-tramites.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tramite $catalogo_tramite)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $catalogo_tramite->update([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'activo' => $request->boolean('activo'),
        ]);

        return redirect()->route('catalogo-tramites.index')->with('success', 'El trámite se actualizó correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tramite $catalogo_tramite)
    {
        $catalogo_tramite->delete();

        return redirect()->route('catalogo-tramites.index')->with('success', 'El trámite se eliminó correctamente.');
    }

    public function cambiarEstado(Tramite $catalogo_tramite)
    {
        $catalogo_tramite->update(['activo' => ! $catalogo_tramite->activo]);

        return redirect()->route('catalogo-tramites.index')->with(
            'success',
            $catalogo_tramite->activo ? 'El trámite se activó correctamente.' : 'El trámite se deshabilitó correctamente.'
        );
    }

    private function generarClave(): string
    {
        do {
            $clave = 'TRM-'.Str::upper(Str::random(8));
        } while (Tramite::withTrashed()->where('clave', $clave)->exists());

        return $clave;
    }
}
