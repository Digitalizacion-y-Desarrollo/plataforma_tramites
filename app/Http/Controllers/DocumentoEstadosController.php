<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\DocumentoEstado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentoEstadosController extends Controller
{
    public function index()
    {
        $estados = DocumentoEstado::query()->orderBy('orden')->orderBy('nombre')->paginate(10);
        $totalEstados = DocumentoEstado::count();
        $estadosActivos = DocumentoEstado::where('activo', true)->count();

        return view('catalogos.documento_estado.index', compact('estados', 'totalEstados', 'estadosActivos'));
    }

    public function create()
    {
        return redirect()->route('catalogo-estados-documentos.index');
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $estado = DocumentoEstado::create(['clave' => $this->generarClave(), ...$datos, 'activo' => $request->boolean('activo')]);
        Auditoria::registrar($request, 'ESTADO_DOCUMENTO_CREADO', 'ESTADO_DOCUMENTO', (string) $estado->id, null, $estado->only(['clave', 'nombre', 'descripcion', 'orden', 'activo']));

        return redirect()->route('catalogo-estados-documentos.index')->with('success', 'El estado de documento se creó correctamente.');
    }

    public function show(DocumentoEstado $catalogo_estado_documento)
    {
        return redirect()->route('catalogo-estados-documentos.index');
    }

    public function edit(DocumentoEstado $catalogo_estado_documento)
    {
        return redirect()->route('catalogo-estados-documentos.index');
    }

    public function update(Request $request, DocumentoEstado $catalogo_estado_documento)
    {
        $anteriores = $catalogo_estado_documento->only(['clave', 'nombre', 'descripcion', 'orden', 'activo']);
        $catalogo_estado_documento->update([...$this->validar($request), 'activo' => $request->boolean('activo')]);
        Auditoria::registrar($request, 'ESTADO_DOCUMENTO_ACTUALIZADO', 'ESTADO_DOCUMENTO', (string) $catalogo_estado_documento->id, $anteriores, $catalogo_estado_documento->only(['clave', 'nombre', 'descripcion', 'orden', 'activo']));

        return redirect()->route('catalogo-estados-documentos.index')->with('success', 'El estado de documento se actualizó correctamente.');
    }

    public function destroy(Request $request, DocumentoEstado $catalogo_estado_documento)
    {
        if (DB::table('solicitud_documentos')->where('documento_estado_id', $catalogo_estado_documento->id)->exists()) {
            return redirect()->route('catalogo-estados-documentos.index')->withErrors(['estado' => 'No se puede eliminar un estado utilizado por documentos de solicitudes.']);
        }

        DB::transaction(function () use ($request, $catalogo_estado_documento): void {
            $anteriores = $catalogo_estado_documento->only(['clave', 'nombre', 'descripcion', 'orden', 'activo']);
            $catalogo_estado_documento->delete();
            Auditoria::registrar($request, 'ESTADO_DOCUMENTO_ELIMINADO', 'ESTADO_DOCUMENTO', (string) $catalogo_estado_documento->id, $anteriores, null);
        });

        return redirect()->route('catalogo-estados-documentos.index')->with('success', 'El estado de documento se eliminó correctamente.');
    }

    public function cambiarEstado(Request $request, DocumentoEstado $catalogo_estado_documento)
    {
        $anterior = $catalogo_estado_documento->activo;
        $catalogo_estado_documento->update(['activo' => ! $catalogo_estado_documento->activo]);
        Auditoria::registrar($request, $catalogo_estado_documento->activo ? 'ESTADO_DOCUMENTO_ACTIVADO' : 'ESTADO_DOCUMENTO_DESACTIVADO', 'ESTADO_DOCUMENTO', (string) $catalogo_estado_documento->id, ['activo' => $anterior], ['activo' => $catalogo_estado_documento->activo]);

        return redirect()->route('catalogo-estados-documentos.index')->with('success', $catalogo_estado_documento->activo ? 'El estado de documento se activó correctamente.' : 'El estado de documento se deshabilitó correctamente.');
    }

    private function validar(Request $request): array
    {
        return $request->validate(['nombre' => ['required', 'string', 'max:100'], 'descripcion' => ['nullable', 'string', 'max:255'], 'orden' => ['required', 'integer', 'min:0'], 'activo' => ['nullable', 'boolean']]);
    }

    private function generarClave(): string
    {
        do {
            $clave = 'EDO-'.Str::upper(Str::random(8));
        } while (DocumentoEstado::withTrashed()->where('clave', $clave)->exists());

        return $clave;
    }
}
