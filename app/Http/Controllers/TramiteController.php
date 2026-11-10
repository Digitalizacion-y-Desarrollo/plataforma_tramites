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
use Illuminate\Validation\ValidationException;

class TramiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(AccesosClient $accesos)
    {
        $filtros = request()->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'departamento_accesos_id' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'in:BORRADOR,PUBLICADO,ARCHIVADO'],
            'activo' => ['nullable', 'boolean'],
            'modalidad' => ['nullable', 'string', 'in:EN_LINEA,PRESENCIAL,MIXTO'],
        ]);

        $tramites = Tramite::query()
            ->with(['estado', 'formularioActivo.formulario'])
            ->when($filtros['q'] ?? null, function ($consulta, string $busqueda): void {
                $consulta->where(function ($subconsulta) use ($busqueda): void {
                    $subconsulta->where('clave', 'like', "%{$busqueda}%")
                        ->orWhere('nombre', 'like', "%{$busqueda}%")
                        ->orWhere('descripcion', 'like', "%{$busqueda}%");
                });
            })
            ->when($filtros['departamento_accesos_id'] ?? null, fn ($consulta, string $departamento) => $consulta->where('departamento_accesos_id', $departamento))
            ->when($filtros['estado'] ?? null, fn ($consulta, string $estado) => $consulta->whereHas('estado', fn ($subconsulta) => $subconsulta->where('clave', $estado)))
            ->when(filled($filtros['activo'] ?? null), fn ($consulta) => $consulta->where('activo', $filtros['activo']))
            ->when($filtros['modalidad'] ?? null, function ($consulta, string $modalidad): void {
                if ($modalidad === 'MIXTO') {
                    $consulta->whereRaw("JSON_SEARCH(modalidades, 'one', 'EN_LINEA') IS NOT NULL")
                        ->whereRaw("JSON_SEARCH(modalidades, 'one', 'PRESENCIAL') IS NOT NULL");
                } else {
                    $consulta->whereRaw("JSON_SEARCH(modalidades, 'one', ?) IS NOT NULL", [$modalidad]);
                }
            })
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();
        $totalTramites = Tramite::count();
        $tramitesActivos = Tramite::where('activo', true)->count();
        $tramitesInactivos = $totalTramites - $tramitesActivos;
        $tramitesPublicados = Tramite::whereHas('estado', fn ($estado) => $estado->where('clave', 'PUBLICADO'))->count();
        $tramitesBorrador = Tramite::where(function ($consulta): void {
            $consulta->whereNull('tramite_estado_id')
                ->orWhereHas('estado', fn ($estado) => $estado->where('clave', 'BORRADOR'));
        })->count();
        $tramitesArchivados = Tramite::whereHas('estado', fn ($estado) => $estado->where('clave', 'ARCHIVADO'))->count();

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

        return view('tramites.index', compact('tramites', 'totalTramites', 'tramitesActivos', 'tramitesInactivos', 'tramitesPublicados', 'tramitesBorrador', 'tramitesArchivados', 'departamentos', 'formularios'));
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
        $request->merge(['requisitos' => $this->normalizarRequisitos($request->input('requisitos', []))]);
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
            'requisitos.*.titulo' => ['required', 'string', 'max:200'],
            'requisitos.*.requisitos' => ['required', 'array', 'min:1'],
            'requisitos.*.requisitos.*' => ['required', 'string', 'max:500'],
            'proceso' => ['nullable', 'array'],
            'proceso.*' => ['string', 'max:500'],
            'preguntas_frecuentes' => ['nullable', 'array'],
            'preguntas_frecuentes.*.pregunta' => ['required_with:preguntas_frecuentes', 'string', 'max:500'],
            'preguntas_frecuentes.*.respuesta' => ['required_with:preguntas_frecuentes', 'string', 'max:2000'],
            'activo' => ['nullable', 'boolean'],
            ...$this->reglasDetalleAdministrativo(),
        ]);

        $departamento = $this->departamentoValido($datos['departamento_accesos_id'], $accesos);
        if (! $departamento) {
            return back()->withInput()->withErrors(['departamento_accesos_id' => 'Selecciona un departamento válido de Accesos.']);
        }

        DB::transaction(function () use ($datos, $request, $departamento): void {
            $tramite = Tramite::create([
                'clave' => $this->generarClave(), 'nombre' => $datos['nombre'], 'descripcion' => $datos['descripcion'] ?? null,
                'departamento_accesos_id' => $departamento['id'], 'departamento_nombre_snapshot' => $departamento['nombre'],
                ...$this->datosPublicos($datos), ...$this->datosDetalleAdministrativo($datos), 'activo' => $request->boolean('activo'),
                'tramite_estado_id' => $this->estadoId('BORRADOR'),
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
        $catalogo_tramite->load(['estado', 'formularioActivo.formulario']);

        return view('tramites.show', ['tramite' => $catalogo_tramite]);
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
        $request->merge(['requisitos' => $this->normalizarRequisitos($request->input('requisitos', []))]);
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
            'requisitos.*.titulo' => ['required', 'string', 'max:200'],
            'requisitos.*.requisitos' => ['required', 'array', 'min:1'],
            'requisitos.*.requisitos.*' => ['required', 'string', 'max:500'],
            'proceso' => ['nullable', 'array'],
            'proceso.*' => ['string', 'max:500'],
            'preguntas_frecuentes' => ['nullable', 'array'],
            'preguntas_frecuentes.*.pregunta' => ['required_with:preguntas_frecuentes', 'string', 'max:500'],
            'preguntas_frecuentes.*.respuesta' => ['required_with:preguntas_frecuentes', 'string', 'max:2000'],
            'activo' => ['nullable', 'boolean'],
            ...$this->reglasDetalleAdministrativo(),
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
                ...$this->datosPublicos($datos), ...$this->datosDetalleAdministrativo($datos), 'activo' => $request->boolean('activo'),
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
        $estadoActual = $catalogo_tramite->estado?->clave ?? 'BORRADOR';

        if ($estadoActual !== 'BORRADOR') {
            return back()->withErrors(['estado' => 'Solo los trámites en borrador pueden publicarse.']);
        }

        $formularioEnBorrador = DB::table('tramite_formulario as vinculo')
            ->join('formulario_versiones as version', 'version.id', '=', 'vinculo.formulario_version_id')
            ->join('formulario_estados as estado', 'estado.id', '=', 'version.formulario_estado_id')
            ->where('vinculo.tramite_id', $catalogo_tramite->id)
            ->where('vinculo.activo', true)
            ->where('estado.clave', 'BORRADOR')
            ->exists();

        if ($formularioEnBorrador) {
            return back()->withErrors(['estado' => 'El formulario activo todavía está en borrador. Publica primero la versión del formulario.']);
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
            'doc_a_obtener', 'vigencia_doc', 'sujeto_inspeccion', 'criterios_resolucion', 'unidad_administrativa_responsable',
            'fund_juridico',
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
            'costo' => isset($datos['costo']) ? ['$'.number_format((float) $datos['costo'], 2).' PESOS'] : null,
            'costo_pago' => $datos['flujo_pago'] === 'SIN_PAGO' ? null : $datos['costo'],
            'flujo_pago' => $datos['flujo_pago'],
            'pago_vigencia_dias' => $datos['flujo_pago'] === 'SIN_PAGO' ? null : $datos['pago_vigencia_dias'],
            'tiempo_estimado' => $datos['tiempo_estimado'] ?? null,
            'modalidades' => $modalidades,
            'requisitos' => $this->normalizarRequisitos($datos['requisitos'] ?? []),
            'proceso' => array_values(array_filter($datos['proceso'] ?? [])),
            'preguntas_frecuentes' => array_values($datos['preguntas_frecuentes'] ?? []),
            'descarga_expira_dias' => in_array('EN_LINEA', $modalidades, true) ? ($datos['descarga_expira_dias'] ?? null) : null,
        ];
    }

    private function reglasDetalleAdministrativo(): array
    {
        return [
            'doc_a_obtener' => ['nullable', 'string', 'max:5000'],
            'vigencia_doc' => ['nullable', 'string', 'max:5000'],
            'sujeto_inspeccion' => ['nullable', 'string', 'max:5000'],
            'criterios_resolucion' => ['nullable', 'string', 'max:5000'],
            'unidad_administrativa_responsable' => ['nullable', 'string', 'max:5000'],
            'fund_juridico' => ['nullable', 'string', 'max:5000'],
        ];
    }

    private function datosDetalleAdministrativo(array $datos): array
    {
        return collect(array_keys($this->reglasDetalleAdministrativo()))
            ->mapWithKeys(fn (string $campo): array => [$campo => filled($datos[$campo] ?? null) ? trim($datos[$campo]) : null])
            ->all();
    }

    private function normalizarRequisitos(mixed $requisitos): array
    {
        if (! is_array($requisitos)) {
            return [];
        }

        $requisitos = array_values($requisitos);

        if ($requisitos !== [] && is_string($requisitos[0] ?? null)) {
            return [[
                'titulo' => 'Requisitos generales',
                'requisitos' => array_values(array_filter($requisitos, fn ($requisito) => is_string($requisito) && filled(trim($requisito)))),
            ]];
        }

        return array_values(array_map(fn (array $seccion) => [
            'titulo' => trim((string) ($seccion['titulo'] ?? '')),
            'requisitos' => array_values(array_filter($seccion['requisitos'] ?? [], fn ($requisito) => is_string($requisito) && filled(trim($requisito)))),
        ], array_filter($requisitos, 'is_array')));
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

        $version = FormularioVersion::query()->with(['formulario', 'estado'])->lockForUpdate()->find($versionId);

        if (! $version || $version->estado?->clave === 'ARCHIVADO') {
            throw ValidationException::withMessages([
                'formulario_version_id' => 'La versión del formulario ya no está disponible. Selecciona otra.',
            ]);
        }

        if ($version->formulario?->departamento_accesos_id !== $departamentoId) {
            throw ValidationException::withMessages([
                'formulario_version_id' => 'El formulario debe pertenecer al departamento seleccionado.',
            ]);
        }

        $tramite->loadMissing('estado');

        if ($tramite->estado?->clave === 'PUBLICADO' && $version->estado?->clave !== 'PUBLICADO') {
            throw ValidationException::withMessages([
                'formulario_version_id' => 'Un trámite publicado solo puede usar una versión publicada del formulario. Publica primero la nueva versión.',
            ]);
        }

        if (DB::table('tramite_formulario')->where('formulario_version_id', $version->id)->where('activo', true)->where('tramite_id', '!=', $tramite->id)->exists()) {
            throw ValidationException::withMessages([
                'formulario_version_id' => 'El formulario ya está asignado a otro trámite.',
            ]);
        }

        $vinculoHistorico = DB::table('tramite_formulario')
            ->where('tramite_id', $tramite->id)
            ->where('formulario_version_id', $version->id)
            ->first();

        if ($vinculoHistorico) {
            DB::table('tramite_formulario')->where('id', $vinculoHistorico->id)->update([
                'fecha_inicio_vigencia' => now(),
                'fecha_fin_vigencia' => null,
                'activo' => true,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('tramite_formulario')->insert([
            'id' => (string) Str::uuid(),
            'tramite_id' => $tramite->id,
            'formulario_version_id' => $version->id,
            'fecha_inicio_vigencia' => now(),
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
