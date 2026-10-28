<?php

namespace App\Http\Controllers;

use App\Integrations\AccesosClient;
use App\Models\AreaOperativa;
use App\Models\Auditoria;
use App\Models\FormularioVersion;
use App\Models\Tramite;
use App\Models\TramiteEstado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TramiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(AccesosClient $accesos)
    {
        $tramites = Tramite::query()
            ->with(['estado', 'formularioActivo.formulario'])
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->paginate(10);
        $totalTramites = Tramite::count();
        $tramitesActivos = Tramite::where('activo', true)->count();
        $tramitesInactivos = $totalTramites - $tramitesActivos;

        try {
            $departamentos = collect($accesos->departamentosActivos())
                ->whereIn('id', AreaOperativa::query()->where('activa', true)->pluck('accesos_departamento_id'))
                ->values()->all();
        } catch (\Throwable) {
            $departamentos = [];
        }

        $versionesActuales = $tramites->getCollection()
            ->flatMap(fn (Tramite $tramite) => $tramite->formularioActivo)
            ->pluck('id');

        $formularios = FormularioVersion::query()
            ->whereHas('estado', fn ($query) => $query->whereIn('clave', ['BORRADOR', 'PUBLICADO']))
            ->whereHas('formulario', fn ($query) => $query->whereNull('deleted_at'))
            ->with('formulario:id,nombre,departamento_accesos_id,departamento_nombre_snapshot')
            ->get()
            ->filter(fn (FormularioVersion $version) => $versionesActuales->contains($version->id)
                || ! DB::table('tramite_formulario')->where('formulario_version_id', $version->id)->where('activo', true)->exists())
            ->values();

        return view('tramites.index', compact('tramites', 'totalTramites', 'tramitesActivos', 'tramitesInactivos', 'departamentos', 'formularios'));
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
    public function store(Request $request, AccesosClient $accesos)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'departamento_accesos_id' => ['required', 'string', 'max:100'],
            'formulario_version_id' => ['nullable', 'uuid'],
            'duracion' => ['nullable', 'string', 'max:100'],
            'costo' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'required_unless:flujo_pago,SIN_PAGO'],
            'flujo_pago' => ['required', 'in:SIN_PAGO,PAGO_DIRECTO,PAGO_POST_VALIDACION'],
            'pago_vigencia_dias' => ['nullable', 'integer', 'min:1', 'max:3650', 'required_unless:flujo_pago,SIN_PAGO'],
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

        $departamento = $this->departamentoValido($datos['departamento_accesos_id'], $accesos);
        if (! $departamento) {
            return back()->withInput()->withErrors(['departamento_accesos_id' => 'Selecciona un departamento válido de Accesos.']);
        }

        DB::transaction(function () use ($datos, $request, $departamento): void {
            $tramite = Tramite::create([
                'clave' => $this->generarClave(), 'nombre' => $datos['nombre'], 'descripcion' => $datos['descripcion'] ?? null,
                'departamento_accesos_id' => $departamento['id'], 'departamento_nombre_snapshot' => $departamento['nombre'],
                ...$this->datosPublicos($datos), 'activo' => $request->boolean('activo'),
            ]);
            $this->sincronizarFormulario($tramite, $datos['formulario_version_id'] ?? null, $departamento['id']);
            Auditoria::registrar($request, 'TRAMITE_CREADO', 'TRAMITE', $tramite->id, null, $this->datosAuditoria($tramite->refresh()));
        });

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
    public function update(Request $request, Tramite $catalogo_tramite, AccesosClient $accesos)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'departamento_accesos_id' => ['required', 'string', 'max:100'],
            'formulario_version_id' => ['nullable', 'uuid'],
            'duracion' => ['nullable', 'string', 'max:100'],
            'costo' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'required_unless:flujo_pago,SIN_PAGO'],
            'flujo_pago' => ['required', 'in:SIN_PAGO,PAGO_DIRECTO,PAGO_POST_VALIDACION'],
            'pago_vigencia_dias' => ['nullable', 'integer', 'min:1', 'max:3650', 'required_unless:flujo_pago,SIN_PAGO'],
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

        $departamento = $this->departamentoValido($datos['departamento_accesos_id'], $accesos);
        if (! $departamento) {
            return back()->withInput()->withErrors(['departamento_accesos_id' => 'Selecciona un departamento válido de Accesos.']);
        }

        DB::transaction(function () use ($catalogo_tramite, $datos, $request, $departamento): void {
            $anteriores = $this->datosAuditoria($catalogo_tramite);
            $catalogo_tramite->update([
                'nombre' => $datos['nombre'], 'descripcion' => $datos['descripcion'] ?? null,
                'departamento_accesos_id' => $departamento['id'], 'departamento_nombre_snapshot' => $departamento['nombre'],
                ...$this->datosPublicos($datos), 'activo' => $request->boolean('activo'),
            ]);
            $this->sincronizarFormulario($catalogo_tramite, $datos['formulario_version_id'] ?? null, $departamento['id']);
            Auditoria::registrar($request, 'TRAMITE_ACTUALIZADO', 'TRAMITE', $catalogo_tramite->id, $anteriores, $this->datosAuditoria($catalogo_tramite->refresh()));
        });

        return redirect()->route('catalogo-tramites.index')->with('success', 'El trámite se actualizó correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Tramite $catalogo_tramite)
    {
        DB::transaction(function () use ($request, $catalogo_tramite): void {
            $anteriores = $this->datosAuditoria($catalogo_tramite);
            $catalogo_tramite->delete();
            Auditoria::registrar($request, 'TRAMITE_ELIMINADO', 'TRAMITE', $catalogo_tramite->id, $anteriores, null);
        });

        return redirect()->route('catalogo-tramites.index')->with('success', 'El trámite se eliminó correctamente.');
    }

    public function cambiarEstado(Request $request, Tramite $catalogo_tramite)
    {
        $anterior = $catalogo_tramite->activo;
        $catalogo_tramite->update(['activo' => ! $catalogo_tramite->activo]);
        Auditoria::registrar($request, 'TRAMITE_ESTADO_CAMBIADO', 'TRAMITE', $catalogo_tramite->id, ['activo' => $anterior], ['activo' => $catalogo_tramite->activo]);

        return redirect()->route('catalogo-tramites.index')->with(
            'success',
            $catalogo_tramite->activo ? 'El trámite se activó correctamente.' : 'El trámite se deshabilitó correctamente.'
        );
    }

    public function publicar(Request $request, Tramite $catalogo_tramite)
    {
        $catalogo_tramite->load('estado');
        if ($catalogo_tramite->estado?->clave !== 'BORRADOR') {
            return back()->withErrors(['estado' => 'Solo los trámites en borrador pueden publicarse.']);
        }

        $anterior = $catalogo_tramite->tramite_estado_id;
        $catalogo_tramite->update(['tramite_estado_id' => $this->estadoId('PUBLICADO')]);
        Auditoria::registrar($request, 'TRAMITE_PUBLICADO', 'TRAMITE', $catalogo_tramite->id, ['tramite_estado_id' => $anterior], ['tramite_estado_id' => $catalogo_tramite->tramite_estado_id]);

        return back()->with('success', 'El trámite se publicó correctamente.');
    }

    public function archivar(Request $request, Tramite $catalogo_tramite)
    {
        $catalogo_tramite->load('estado');
        if ($catalogo_tramite->estado?->clave !== 'PUBLICADO') {
            return back()->withErrors(['estado' => 'Solo los trámites publicados pueden archivarse.']);
        }

        $anterior = $catalogo_tramite->tramite_estado_id;
        $catalogo_tramite->update(['tramite_estado_id' => $this->estadoId('ARCHIVADO')]);
        Auditoria::registrar($request, 'TRAMITE_ARCHIVADO', 'TRAMITE', $catalogo_tramite->id, ['tramite_estado_id' => $anterior], ['tramite_estado_id' => $catalogo_tramite->tramite_estado_id]);

        return back()->with('success', 'El trámite se archivó correctamente.');
    }

    private function datosAuditoria(Tramite $tramite): array
    {
        return $tramite->only([
            'clave', 'nombre', 'descripcion', 'departamento_accesos_id', 'departamento_nombre_snapshot',
            'activo', 'tramite_estado_id', 'duracion', 'costo', 'costo_pago', 'flujo_pago',
            'pago_vigencia_dias', 'tiempo_estimado', 'modalidades', 'requisitos', 'proceso',
            'preguntas_frecuentes', 'descarga_expira_dias',
        ]);
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
            'costo' => isset($datos['costo']) ? '$'.number_format((float) $datos['costo'], 2).' MXN' : null,
            'costo_pago' => $datos['flujo_pago'] === 'SIN_PAGO' ? null : $datos['costo'],
            'flujo_pago' => $datos['flujo_pago'],
            'pago_vigencia_dias' => $datos['flujo_pago'] === 'SIN_PAGO' ? null : $datos['pago_vigencia_dias'],
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

    private function departamentoValido(string $id, AccesosClient $accesos): ?array
    {
        try {
            $departamento = collect($accesos->departamentosActivos())->firstWhere('id', $id);
        } catch (\Throwable) {
            return null;
        }

        return $departamento
            && $departamento['parent_id'] === null
            && AreaOperativa::query()->where('accesos_departamento_id', $departamento['id'])->where('activa', true)->exists()
            ? $departamento
            : null;
    }

    private function sincronizarFormulario(Tramite $tramite, ?string $versionId, string $departamentoId): void
    {
        $actual = DB::table('tramite_formulario')->where('tramite_id', $tramite->id)->where('activo', true)->lockForUpdate()->first();
        if (($actual->formulario_version_id ?? null) === $versionId) {
            return;
        }

        if ($actual) {
            DB::table('tramite_formulario')->where('id', $actual->id)->update(['activo' => false, 'fecha_fin_vigencia' => now(), 'updated_at' => now()]);
        }
        if (! $versionId) {
            return;
        }

        $version = FormularioVersion::query()->with(['formulario', 'estado'])->lockForUpdate()->findOrFail($versionId);
        abort_unless(in_array($version->estado?->clave, ['BORRADOR', 'PUBLICADO'], true)
            && $version->formulario?->departamento_accesos_id === $departamentoId, 422, 'El formulario debe pertenecer al departamento seleccionado.');
        abort_if(DB::table('tramite_formulario')->where('formulario_version_id', $version->id)->where('activo', true)->exists(), 422, 'El formulario ya está asignado a otro trámite.');

        DB::table('tramite_formulario')->insert(['id' => (string) Str::uuid(), 'tramite_id' => $tramite->id, 'formulario_version_id' => $version->id, 'fecha_inicio_vigencia' => now(), 'activo' => true, 'created_at' => now(), 'updated_at' => now()]);
    }
}
