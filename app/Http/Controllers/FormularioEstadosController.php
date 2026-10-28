<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\FormularioEstado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormularioEstadosController extends Controller
{
    private const ESTADOS_SISTEMA = ['BORRADOR', 'PUBLICADO', 'ARCHIVADO'];

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
        $estado = FormularioEstado::create(['clave' => $this->generarClave(), ...$datos, 'activo' => $request->boolean('activo')]);
        Auditoria::registrar($request, 'ESTADO_FORMULARIO_CREADO', 'ESTADO_FORMULARIO', (string) $estado->id, null, $estado->only(['clave', 'nombre', 'descripcion', 'orden', 'activo']));

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
        if (in_array($catalogo_estado_formulario->clave, self::ESTADOS_SISTEMA, true) && ! $request->boolean('activo')) {
            return back()->withInput()->withErrors(['estado' => 'Este estado es parte del flujo del sistema y no puede desactivarse.']);
        }

        $datos = $this->validar($request);
        $anteriores = $catalogo_estado_formulario->only(['clave', 'nombre', 'descripcion', 'orden', 'activo']);
        $catalogo_estado_formulario->update([...$datos, 'activo' => $request->boolean('activo')]);
        Auditoria::registrar($request, 'ESTADO_FORMULARIO_ACTUALIZADO', 'ESTADO_FORMULARIO', (string) $catalogo_estado_formulario->id, $anteriores, $catalogo_estado_formulario->only(['clave', 'nombre', 'descripcion', 'orden', 'activo']));

        return redirect()->route('catalogo-estados-formularios.index')->with('success', 'El estado de formulario se actualizó correctamente.');
    }

    public function destroy(Request $request, FormularioEstado $catalogo_estado_formulario)
    {
        if (in_array($catalogo_estado_formulario->clave, self::ESTADOS_SISTEMA, true)) {
            return redirect()->route('catalogo-estados-formularios.index')->withErrors(['estado' => 'Este estado es parte del flujo del sistema y no puede eliminarse.']);
        }

        if (DB::table('formulario_versiones')->where('formulario_estado_id', $catalogo_estado_formulario->id)->exists()) {
            return redirect()->route('catalogo-estados-formularios.index')->withErrors(['estado' => 'No se puede eliminar un estado utilizado por versiones de formularios.']);
        }

        DB::transaction(function () use ($request, $catalogo_estado_formulario): void {
            $anteriores = $catalogo_estado_formulario->only(['clave', 'nombre', 'descripcion', 'orden', 'activo']);
            $catalogo_estado_formulario->delete();
            Auditoria::registrar($request, 'ESTADO_FORMULARIO_ELIMINADO', 'ESTADO_FORMULARIO', (string) $catalogo_estado_formulario->id, $anteriores, null);
        });

        return redirect()->route('catalogo-estados-formularios.index')->with('success', 'El estado de formulario se eliminó correctamente.');
    }

    public function cambiarEstado(Request $request, FormularioEstado $catalogo_estado_formulario)
    {
        if ($catalogo_estado_formulario->activo && in_array($catalogo_estado_formulario->clave, self::ESTADOS_SISTEMA, true)) {
            return redirect()->route('catalogo-estados-formularios.index')->withErrors(['estado' => 'Este estado es parte del flujo del sistema y no puede desactivarse.']);
        }

        $anterior = $catalogo_estado_formulario->activo;
        $catalogo_estado_formulario->update(['activo' => ! $catalogo_estado_formulario->activo]);
        Auditoria::registrar($request, $catalogo_estado_formulario->activo ? 'ESTADO_FORMULARIO_ACTIVADO' : 'ESTADO_FORMULARIO_DESACTIVADO', 'ESTADO_FORMULARIO', (string) $catalogo_estado_formulario->id, ['activo' => $anterior], ['activo' => $catalogo_estado_formulario->activo]);

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
