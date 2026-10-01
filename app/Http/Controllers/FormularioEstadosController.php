<?php

namespace App\Http\Controllers;

use App\Models\FormularioEstado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormularioEstadosController extends Controller
{
    public function index()
    {
        $estados = FormularioEstado::query()->orderBy('orden')->orderBy('nombre')->paginate(10);
        $totalEstados = FormularioEstado::count();
        $estadosActivos = FormularioEstado::where('activo', true)->count();

        return view('catalogos.formulario_estado.index', compact('estados', 'totalEstados', 'estadosActivos'));
    }

    public function create()
    {
        return redirect()->route('catalogo-estados-formularios.index');
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        FormularioEstado::create(['clave' => $this->generarClave(), ...$datos, 'activo' => $request->boolean('activo')]);

        return redirect()->route('catalogo-estados-formularios.index')->with('success', 'El estado de formulario se creó correctamente.');
    }

    public function show(FormularioEstado $catalogo_estado_formulario)
    {
        return redirect()->route('catalogo-estados-formularios.index');
    }

    public function edit(FormularioEstado $catalogo_estado_formulario)
    {
        return redirect()->route('catalogo-estados-formularios.index');
    }

    public function update(Request $request, FormularioEstado $catalogo_estado_formulario)
    {
        $datos = $this->validar($request);
        $catalogo_estado_formulario->update([...$datos, 'activo' => $request->boolean('activo')]);

        return redirect()->route('catalogo-estados-formularios.index')->with('success', 'El estado de formulario se actualizó correctamente.');
    }

    public function destroy(FormularioEstado $catalogo_estado_formulario)
    {
        if (DB::table('formulario_versiones')->where('formulario_estado_id', $catalogo_estado_formulario->id)->exists()) {
            return redirect()->route('catalogo-estados-formularios.index')->withErrors(['estado' => 'No se puede eliminar un estado utilizado por versiones de formularios.']);
        }

        $catalogo_estado_formulario->delete();

        return redirect()->route('catalogo-estados-formularios.index')->with('success', 'El estado de formulario se eliminó correctamente.');
    }

    public function cambiarEstado(FormularioEstado $catalogo_estado_formulario)
    {
        $catalogo_estado_formulario->update(['activo' => ! $catalogo_estado_formulario->activo]);

        return redirect()->route('catalogo-estados-formularios.index')->with('success', $catalogo_estado_formulario->activo ? 'El estado de formulario se activó correctamente.' : 'El estado de formulario se deshabilitó correctamente.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'orden' => ['required', 'integer', 'min:0'],
            'activo' => ['nullable', 'boolean'],
        ]);
    }

    private function generarClave(): string
    {
        do {
            $clave = 'EFO-'.Str::upper(Str::random(8));
        } while (FormularioEstado::withTrashed()->where('clave', $clave)->exists());

        return $clave;
    }
}
