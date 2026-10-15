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
            ->with('estado')
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
            'duracion' => ['nullable', 'string', 'max:100'],
            'costo' => ['nullable', 'string', 'max:100'],
            'tiempo_estimado' => ['nullable', 'string', 'max:100'],
            'modalidad' => ['nullable', 'string', 'in:EN_LINEA,PRESENCIAL,MIXTO'],
            'descarga_expira_dias' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'requisitos' => ['nullable', 'array'],
            'requisitos.*' => ['string', 'max:500'],
            'proceso' => ['nullable', 'array'],
            'proceso.*' => ['string', 'max:500'],
            'preguntas_frecuentes' => ['nullable', 'array'],
            'preguntas_frecuentes.*.pregunta' => ['required_with:preguntas_frecuentes', 'string', 'max:500'],
            'preguntas_frecuentes.*.respuesta' => ['required_with:preguntas_frecuentes', 'string', 'max:2000'],
            'activo' => ['nullable', 'boolean'],
        ]);

        Tramite::create([
            'clave' => $this->generarClave(),
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            ...$this->datosPublicos($datos),
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
            'duracion' => ['nullable', 'string', 'max:100'],
            'costo' => ['nullable', 'string', 'max:100'],
            'tiempo_estimado' => ['nullable', 'string', 'max:100'],
            'modalidad' => ['nullable', 'string', 'in:EN_LINEA,PRESENCIAL,MIXTO'],
            'descarga_expira_dias' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'requisitos' => ['nullable', 'array'],
            'requisitos.*' => ['string', 'max:500'],
            'proceso' => ['nullable', 'array'],
            'proceso.*' => ['string', 'max:500'],
            'preguntas_frecuentes' => ['nullable', 'array'],
            'preguntas_frecuentes.*.pregunta' => ['required_with:preguntas_frecuentes', 'string', 'max:500'],
            'preguntas_frecuentes.*.respuesta' => ['required_with:preguntas_frecuentes', 'string', 'max:2000'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $catalogo_tramite->update([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            ...$this->datosPublicos($datos),
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

    public function publicar(Tramite $catalogo_tramite)
    {
        $catalogo_tramite->load('estado');
        if ($catalogo_tramite->estado?->clave !== 'BORRADOR') {
            return back()->withErrors(['estado' => 'Solo los trámites en borrador pueden publicarse.']);
        }

        $catalogo_tramite->update(['tramite_estado_id' => $this->estadoId('PUBLICADO')]);

        return back()->with('success', 'El trámite se publicó correctamente.');
    }

    public function archivar(Tramite $catalogo_tramite)
    {
        $catalogo_tramite->load('estado');
        if ($catalogo_tramite->estado?->clave !== 'PUBLICADO') {
            return back()->withErrors(['estado' => 'Solo los trámites publicados pueden archivarse.']);
        }

        $catalogo_tramite->update(['tramite_estado_id' => $this->estadoId('ARCHIVADO')]);

        return back()->with('success', 'El trámite se archivó correctamente.');
    }

    private function generarClave(): string
    {
        do {
            $clave = 'TRM-'.Str::upper(Str::random(8));
        } while (Tramite::withTrashed()->where('clave', $clave)->exists());

        return $clave;
    }

    private function datosPublicos(array $datos): array
    {
        $modalidades = match ($datos['modalidad'] ?? null) {
            'EN_LINEA' => ['EN_LINEA'],
            'PRESENCIAL' => ['PRESENCIAL'],
            'MIXTO' => ['EN_LINEA', 'PRESENCIAL'],
            default => [],
        };

        return [
            'duracion' => $datos['duracion'] ?? null,
            'costo' => $datos['costo'] ?? null,
            'tiempo_estimado' => $datos['tiempo_estimado'] ?? null,
            'modalidades' => $modalidades,
            'requisitos' => array_values(array_filter($datos['requisitos'] ?? [])),
            'proceso' => array_values(array_filter($datos['proceso'] ?? [])),
            'preguntas_frecuentes' => array_values($datos['preguntas_frecuentes'] ?? []),
            'descarga_expira_dias' => in_array('EN_LINEA', $modalidades, true) ? ($datos['descarga_expira_dias'] ?? null) : null,
        ];
    }

    private function estadoId(string $clave): int
    {
        return TramiteEstado::query()->where('clave', $clave)->where('activo', true)->firstOrFail()->id;
    }
}
