<?php

namespace App\Http\Controllers;

use App\Integrations\AccesosClient;
use App\Models\Auditoria;
use App\Models\CampoCondicion;
use App\Models\Formulario;
use App\Models\FormularioCampo;
use App\Models\FormularioEstado;
use App\Models\FormularioGrupoCampo;
use App\Models\FormularioSeccion;
use App\Models\FormularioVersion;
use App\Models\Tramite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FormularioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'estado' => ['nullable', Rule::in(['BORRADOR', 'PUBLICADO', 'ARCHIVADO'])],
            'tramite_id' => ['nullable', 'integer', 'exists:tramites,id'],
            'departamento' => ['nullable', 'string', 'max:200'],
            'filtro' => ['nullable', Rule::in(['RECIENTES'])],
        ]);

        $consultaBase = Formulario::query();
        $formulariosResumen = (clone $consultaBase)
            ->with(['ultimaVersion.estado', 'ultimaVersion.tramites'])
            ->get();

        $conteos = [
            'todos' => $formulariosResumen->count(),
            'RECIENTES' => $formulariosResumen->filter(fn (Formulario $formulario) => $formulario->updated_at?->gte(now()->subDays(7)))->count(),
            'BORRADOR' => $formulariosResumen->filter(fn (Formulario $formulario) => $formulario->ultimaVersion?->estado?->clave === 'BORRADOR')->count(),
            'PUBLICADO' => $formulariosResumen->filter(fn (Formulario $formulario) => $formulario->ultimaVersion?->estado?->clave === 'PUBLICADO')->count(),
            'ARCHIVADO' => $formulariosResumen->filter(fn (Formulario $formulario) => $formulario->ultimaVersion?->estado?->clave === 'ARCHIVADO')->count(),
            'ASIGNADOS' => $formulariosResumen->filter(fn (Formulario $formulario) => $formulario->ultimaVersion?->tramites->contains(fn (Tramite $tramite) => (bool) $tramite->pivot->activo))->count(),
        ];

        $consulta = (clone $consultaBase)
            ->with([
                'ultimaVersion.estado',
                'ultimaVersion.tramites',
                'ultimaVersion.secciones.campos',
            ]);

        if (filled($filtros['q'] ?? null)) {
            $busqueda = trim($filtros['q']);

            $consulta->where(function ($consulta) use ($busqueda): void {
                $consulta->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('descripcion', 'like', "%{$busqueda}%")
                    ->orWhere('departamento_nombre_snapshot', 'like', "%{$busqueda}%")
                    ->orWhere('subdepartamento_nombre_snapshot', 'like', "%{$busqueda}%")
                    ->orWhereHas('ultimaVersion.tramites', fn ($tramites) => $tramites->where('tramites.nombre', 'like', "%{$busqueda}%"));
            });
        }

        if (filled($filtros['estado'] ?? null)) {
            $consulta->whereHas('ultimaVersion.estado', fn ($estado) => $estado->where('clave', $filtros['estado']));
        }

        if (filled($filtros['tramite_id'] ?? null)) {
            $consulta->whereHas('ultimaVersion.tramites', fn ($tramites) => $tramites
                ->where('tramites.id', $filtros['tramite_id'])
                ->where('tramite_formulario.activo', true));
        }

        if (filled($filtros['departamento'] ?? null)) {
            $consulta->where(function ($consulta) use ($filtros): void {
                $consulta->where('departamento_nombre_snapshot', $filtros['departamento'])
                    ->orWhere('subdepartamento_nombre_snapshot', $filtros['departamento']);
            });
        }

        if (($filtros['filtro'] ?? null) === 'RECIENTES') {
            $consulta->where('updated_at', '>=', now()->subDays(7));
        }

        $formularios = $consulta->latest()->paginate(10)->withQueryString();

        $departamentosDisponibles = Formulario::query()
            ->whereNotNull('departamento_nombre_snapshot')
            ->select('departamento_nombre_snapshot')
            ->distinct()
            ->orderBy('departamento_nombre_snapshot')
            ->pluck('departamento_nombre_snapshot');

        $tramitesDisponibles = Tramite::query()
            ->whereIn('id', DB::table('tramite_formulario')->where('activo', true)->pluck('tramite_id'))
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $versionesVigentes = $formulariosResumen->pluck('ultimaVersion.id')->filter()->values()->all();

        $camposMasUsados = empty($versionesVigentes)
            ? collect()
            : FormularioCampo::query()
                ->whereHas('seccion', fn ($seccion) => $seccion->whereIn('formulario_version_id', $versionesVigentes))
                ->where('activo', true)
                ->select('tipo', DB::raw('count(*) as total'))
                ->groupBy('tipo')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

        $actividadReciente = Auditoria::query()
            ->where('accion', 'like', 'FORMULARIO%')
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('formularios.index', compact(
            'formularios',
            'conteos',
            'filtros',
            'departamentosDisponibles',
            'tramitesDisponibles',
            'camposMasUsados',
            'actividadReciente',
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(AccesosClient $accesos)
    {
        try {
            $departamentos = $accesos->departamentosActivos();
        } catch (\Throwable $exception) {
            Log::warning('No se pudo cargar el catálogo de departamentos de Accesos.', ['exception' => $exception]);
            $departamentos = [];
        }

        return view('formularios.create', compact('departamentos'));
    }

    public function createDuplicado(Formulario $formulario, AccesosClient $accesos)
    {
        try {
            $departamentos = $accesos->departamentosActivos();
        } catch (\Throwable $exception) {
            Log::warning('No se pudo cargar el catálogo de departamentos de Accesos.', ['exception' => $exception]);
            $departamentos = [];
        }

        return view('formularios.duplicar', compact('formulario', 'departamentos'));
    }

    public function storeDuplicado(Request $request, Formulario $formulario, AccesosClient $accesos)
    {
        $datos = $request->validate([
            'departamento_accesos_id' => ['required', 'string', 'max:100'],
            'subdepartamento_accesos_id' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $departamentos = collect($accesos->departamentosActivos());
            $departamento = $departamentos->firstWhere('id', $datos['departamento_accesos_id']);
        } catch (\Throwable) {
            return back()->withInput()->withErrors([
                'departamento_accesos_id' => 'No fue posible validar el departamento con Accesos. Intenta nuevamente.',
            ]);
        }

        if (! $departamento || $departamento['parent_id'] !== null) {
            return back()->withInput()->withErrors([
                'departamento_accesos_id' => 'Selecciona un departamento válido de la lista proporcionada por Accesos.',
            ]);
        }

        $subdepartamento = null;

        if (! empty($datos['subdepartamento_accesos_id'])) {
            $subdepartamento = $departamentos->firstWhere('id', $datos['subdepartamento_accesos_id']);

            if (! $subdepartamento || $subdepartamento['parent_id'] !== $departamento['id']) {
                return back()->withInput()->withErrors([
                    'subdepartamento_accesos_id' => 'Selecciona un subdepartamento válido para el departamento elegido.',
                ]);
            }
        }

        $versionOrigen = $formulario->ultimaVersion()
            ->with([
                'secciones.campos.opciones',
                'secciones.campos.condicionesDestino',
                'gruposCampos.campos',
                'gruposCampos.condicionesDestino',
            ])
            ->firstOrFail();
        $estadoBorrador = FormularioEstado::query()
            ->where('clave', 'BORRADOR')
            ->where('activo', true)
            ->firstOrFail();
        $requisitosOrigen = DB::table('requisito_documentos')
            ->where('formulario_version_id', $versionOrigen->id)
            ->orderBy('orden')
            ->get();
        $creadoPor = session('accesos.id');

        $duplicado = DB::transaction(function () use ($formulario, $versionOrigen, $requisitosOrigen, $estadoBorrador, $departamento, $subdepartamento, $creadoPor): Formulario {
            $duplicado = Formulario::create([
                'id' => (string) Str::uuid(),
                'nombre' => $formulario->nombre.' (copia)',
                'descripcion' => $formulario->descripcion,
                'departamento_accesos_id' => $departamento['id'],
                'departamento_nombre_snapshot' => $departamento['nombre'],
                'subdepartamento_accesos_id' => $subdepartamento['id'] ?? null,
                'subdepartamento_nombre_snapshot' => $subdepartamento['nombre'] ?? null,
                'creado_por_accesos_id' => $creadoPor,
            ]);
            $versionDuplicada = $duplicado->versiones()->create([
                'id' => (string) Str::uuid(),
                'formulario_estado_id' => $estadoBorrador->id,
                'numero_version' => 1,
                'creado_por_accesos_id' => $creadoPor,
            ]);
            $secciones = [];
            $campos = [];
            $grupos = [];

            foreach ($versionOrigen->secciones as $seccionOrigen) {
                $seccionDuplicada = $versionDuplicada->secciones()->create([
                    'id' => (string) Str::uuid(),
                    'clave' => $seccionOrigen->clave,
                    'titulo' => $seccionOrigen->titulo,
                    'descripcion' => $seccionOrigen->descripcion,
                    'orden' => $seccionOrigen->orden,
                    'activo' => $seccionOrigen->activo,
                ]);
                $secciones[$seccionOrigen->id] = $seccionDuplicada;

                foreach ($seccionOrigen->campos as $campoOrigen) {
                    $campoDuplicado = $seccionDuplicada->campos()->create([
                        'id' => (string) Str::uuid(),
                        'clave' => $campoOrigen->clave,
                        'etiqueta' => $campoOrigen->etiqueta,
                        'tipo' => $campoOrigen->tipo,
                        'requerido' => $campoOrigen->requerido,
                        'orden' => $campoOrigen->orden,
                        'configuracion' => $campoOrigen->configuracion,
                        'activo' => $campoOrigen->activo,
                    ]);
                    $campos[$campoOrigen->id] = $campoDuplicado;

                    foreach ($campoOrigen->opciones as $opcionOrigen) {
                        $campoDuplicado->opciones()->create([
                            'id' => (string) Str::uuid(),
                            'valor' => $opcionOrigen->valor,
                            'etiqueta' => $opcionOrigen->etiqueta,
                            'orden' => $opcionOrigen->orden,
                            'activo' => $opcionOrigen->activo,
                        ]);
                    }
                }
            }

            foreach ($versionOrigen->gruposCampos as $grupoOrigen) {
                $grupoDuplicado = $versionDuplicada->gruposCampos()->create([
                    'id' => (string) Str::uuid(),
                    'titulo' => $grupoOrigen->titulo,
                    'orden' => $grupoOrigen->orden,
                    'activo' => $grupoOrigen->activo,
                ]);
                $grupos[$grupoOrigen->id] = $grupoDuplicado;
                $grupoDuplicado->campos()->attach(collect($grupoOrigen->campos)
                    ->mapWithKeys(fn (FormularioCampo $campoOrigen) => [
                        $campos[$campoOrigen->id]->id => ['id' => (string) Str::uuid()],
                    ])->all());
            }

            foreach ($versionOrigen->secciones as $seccionOrigen) {
                foreach ($seccionOrigen->campos as $campoOrigen) {
                    foreach ($campoOrigen->condicionesDestino as $condicionOrigen) {
                        $campos[$campoOrigen->id]->condicionesDestino()->create([
                            'id' => (string) Str::uuid(),
                            'campo_origen_id' => $campos[$condicionOrigen->campo_origen_id]->id,
                            'operador' => $condicionOrigen->operador,
                            'valor_comparacion' => $condicionOrigen->valor_comparacion,
                            'accion' => $condicionOrigen->accion,
                            'grupo' => $condicionOrigen->grupo,
                        ]);
                    }
                }
            }

            foreach ($versionOrigen->gruposCampos as $grupoOrigen) {
                foreach ($grupoOrigen->condicionesDestino as $condicionOrigen) {
                    $grupos[$grupoOrigen->id]->condicionesDestino()->create([
                        'id' => (string) Str::uuid(),
                        'campo_origen_id' => $campos[$condicionOrigen->campo_origen_id]->id,
                        'operador' => $condicionOrigen->operador,
                        'valor_comparacion' => $condicionOrigen->valor_comparacion,
                        'accion' => $condicionOrigen->accion,
                        'grupo' => $condicionOrigen->grupo,
                    ]);
                }
            }

            foreach ($requisitosOrigen as $requisitoOrigen) {
                DB::table('requisito_documentos')->insert([
                    'id' => (string) Str::uuid(),
                    'formulario_version_id' => $versionDuplicada->id,
                    'clave' => $requisitoOrigen->clave,
                    'nombre' => $requisitoOrigen->nombre,
                    'descripcion' => $requisitoOrigen->descripcion,
                    'obligatorio' => $requisitoOrigen->obligatorio,
                    'formatos_permitidos' => $requisitoOrigen->formatos_permitidos,
                    'tamano_maximo_mb' => $requisitoOrigen->tamano_maximo_mb,
                    'cantidad_maxima' => $requisitoOrigen->cantidad_maxima,
                    'orden' => $requisitoOrigen->orden,
                    'condicion' => $requisitoOrigen->condicion,
                    'activo' => $requisitoOrigen->activo,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $duplicado;
        });
        Auditoria::registrar($request, 'FORMULARIO_DUPLICADO', 'FORMULARIO', $duplicado->id, ['formulario_origen_id' => $formulario->id], ['nombre' => $duplicado->nombre, 'departamento_accesos_id' => $duplicado->departamento_accesos_id]);

        return redirect()->route('formulario.edit', $duplicado)
            ->with('success', 'El formulario se duplicó como borrador. Asígnalo a un trámite cuando esté listo.');
    }

    /**
     * Crea una nueva versión en borrador a partir de la última versión del formulario.
     */
    public function storeVersion(Request $request, Formulario $formulario)
    {
        $versionOrigen = $formulario->versiones()
            ->with('estado')
            ->orderByDesc('numero_version')
            ->firstOrFail();

        if ($versionOrigen->estado?->clave === 'BORRADOR') {
            return back()->withErrors(['version' => 'Ya existe una versión en borrador. Termínala y publícala antes de crear otra.']);
        }

        $estadoBorrador = FormularioEstado::query()
            ->where('clave', 'BORRADOR')
            ->where('activo', true)
            ->firstOrFail();

        $nuevaVersion = DB::transaction(function () use ($request, $formulario, $versionOrigen, $estadoBorrador): FormularioVersion {
            $numeroVersion = ((int) DB::table('formulario_versiones')
                ->where('formulario_id', $formulario->id)
                ->lockForUpdate()
                ->max('numero_version')) + 1;

            $nuevaVersion = $formulario->versiones()->create([
                'id' => (string) Str::uuid(),
                'formulario_estado_id' => $estadoBorrador->id,
                'numero_version' => $numeroVersion,
                'creado_por_accesos_id' => session('accesos.id'),
            ]);

            $this->copiarContenidoVersion($versionOrigen, $nuevaVersion);

            Auditoria::registrar(
                $request,
                'FORMULARIO_VERSION_CREADA',
                'FORMULARIO_VERSION',
                $nuevaVersion->id,
                ['formulario_id' => $formulario->id, 'version_origen_id' => $versionOrigen->id],
                ['numero_version' => $numeroVersion, 'estado' => 'BORRADOR'],
            );

            return $nuevaVersion;
        });

        return redirect()->route('formulario.edit', $formulario)
            ->with('success', 'Se creó la versión '.$nuevaVersion->numero_version.' en borrador. Ajusta el contenido y publícala cuando esté lista.');
    }

    /**
     * Publica una versión en borrador y conserva las versiones anteriores como históricas.
     */
    public function publicarVersion(Request $request, Formulario $formulario, FormularioVersion $version)
    {
        abort_unless($version->formulario_id === $formulario->id, 404);

        $version->load('estado');

        if ($version->estado?->clave !== 'BORRADOR') {
            return back()->withErrors(['version' => 'Solo las versiones en borrador pueden publicarse.']);
        }

        $secciones = $version->secciones()
            ->where('activo', true)
            ->withCount(['campos' => fn ($campos) => $campos->where('activo', true)])
            ->get();

        if ($secciones->isEmpty() || $secciones->contains(fn (FormularioSeccion $seccion) => (int) $seccion->campos_count === 0)) {
            return back()->withErrors(['version' => 'Agrega al menos una sección con un campo activo antes de publicar la versión.']);
        }

        $estadoPublicado = FormularioEstado::query()->where('clave', 'PUBLICADO')->where('activo', true)->firstOrFail();
        $estadoArchivado = FormularioEstado::query()->where('clave', 'ARCHIVADO')->where('activo', true)->firstOrFail();

        DB::transaction(function () use ($request, $formulario, $version, $estadoPublicado, $estadoArchivado): void {
            $versionesAnteriores = FormularioVersion::query()
                ->where('formulario_id', $formulario->id)
                ->whereKeyNot($version->id)
                ->whereHas('estado', fn ($estado) => $estado->where('clave', 'PUBLICADO'))
                ->get();

            $tramitesVinculados = collect();

            foreach ($versionesAnteriores as $versionAnterior) {
                $tramitesVinculados = $tramitesVinculados->merge(
                    DB::table('tramite_formulario')
                        ->where('formulario_version_id', $versionAnterior->id)
                        ->where('activo', true)
                        ->pluck('tramite_id'),
                );

                $versionAnterior->update(['formulario_estado_id' => $estadoArchivado->id]);

                DB::table('tramite_formulario')
                    ->where('formulario_version_id', $versionAnterior->id)
                    ->where('activo', true)
                    ->update([
                        'activo' => false,
                        'fecha_fin_vigencia' => now(),
                        'updated_at' => now(),
                    ]);

                Auditoria::registrar(
                    $request,
                    'FORMULARIO_VERSION_ARCHIVADA',
                    'FORMULARIO_VERSION',
                    $versionAnterior->id,
                    ['formulario_estado_id' => $estadoPublicado->id],
                    ['formulario_estado_id' => $estadoArchivado->id, 'motivo' => 'Publicación de nueva versión'],
                );
            }

            $version->update([
                'formulario_estado_id' => $estadoPublicado->id,
                'fecha_publicacion' => now(),
                'publicado_por_accesos_id' => session('accesos.id'),
            ]);

            $tramitesVinculados = $tramitesVinculados
                ->merge(DB::table('tramite_formulario')
                    ->where('formulario_version_id', $version->id)
                    ->where('activo', true)
                    ->pluck('tramite_id'))
                ->unique()
                ->values();

            foreach ($tramitesVinculados as $tramiteId) {
                DB::table('tramite_formulario')
                    ->where('tramite_id', $tramiteId)
                    ->where('formulario_version_id', '!=', $version->id)
                    ->where('activo', true)
                    ->update([
                        'activo' => false,
                        'fecha_fin_vigencia' => now(),
                        'updated_at' => now(),
                    ]);

                $vinculoVersion = DB::table('tramite_formulario')
                    ->where('tramite_id', $tramiteId)
                    ->where('formulario_version_id', $version->id)
                    ->first();

                if ($vinculoVersion) {
                    DB::table('tramite_formulario')->where('id', $vinculoVersion->id)->update([
                        'fecha_inicio_vigencia' => now(),
                        'fecha_fin_vigencia' => null,
                        'activo' => true,
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('tramite_formulario')->insert([
                        'id' => (string) Str::uuid(),
                        'tramite_id' => $tramiteId,
                        'formulario_version_id' => $version->id,
                        'fecha_inicio_vigencia' => now(),
                        'fecha_fin_vigencia' => null,
                        'activo' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            Auditoria::registrar(
                $request,
                'FORMULARIO_VERSION_PUBLICADA',
                'FORMULARIO_VERSION',
                $version->id,
                ['formulario_estado_id' => $version->getOriginal('formulario_estado_id')],
                ['formulario_estado_id' => $estadoPublicado->id, 'tramites_vinculados' => $tramitesVinculados->all()],
            );
        });

        return back()->with('success', 'La versión '.$version->numero_version.' se publicó correctamente.');
    }

    /**
     * Archiva una versión publicada sin eliminar su contenido histórico.
     */
    public function archivarVersion(Request $request, Formulario $formulario, FormularioVersion $version)
    {
        abort_unless($version->formulario_id === $formulario->id, 404);

        $version->load('estado');

        if ($version->estado?->clave !== 'PUBLICADO') {
            return back()->withErrors(['version' => 'Solo las versiones publicadas pueden archivarse.']);
        }

        $versionActivaEnTramitesPublicados = DB::table('tramite_formulario as vinculo')
            ->join('tramites as tramite', 'tramite.id', '=', 'vinculo.tramite_id')
            ->join('tramite_estados as estado', 'estado.id', '=', 'tramite.tramite_estado_id')
            ->where('vinculo.formulario_version_id', $version->id)
            ->where('vinculo.activo', true)
            ->where('estado.clave', 'PUBLICADO')
            ->exists();

        if ($versionActivaEnTramitesPublicados) {
            return back()->withErrors(['version' => 'No puedes archivar esta versión mientras sea el formulario activo de un trámite publicado. Publica otra versión o archiva primero el trámite.']);
        }

        $estadoArchivado = FormularioEstado::query()->where('clave', 'ARCHIVADO')->where('activo', true)->firstOrFail();

        DB::transaction(function () use ($request, $version, $estadoArchivado): void {
            $anterior = $version->formulario_estado_id;

            $version->update(['formulario_estado_id' => $estadoArchivado->id]);

            DB::table('tramite_formulario')
                ->where('formulario_version_id', $version->id)
                ->where('activo', true)
                ->update([
                    'activo' => false,
                    'fecha_fin_vigencia' => now(),
                    'updated_at' => now(),
                ]);

            Auditoria::registrar(
                $request,
                'FORMULARIO_VERSION_ARCHIVADA',
                'FORMULARIO_VERSION',
                $version->id,
                ['formulario_estado_id' => $anterior],
                ['formulario_estado_id' => $estadoArchivado->id],
            );
        });

        return back()->with('success', 'La versión '.$version->numero_version.' se archivó. Puedes crear una nueva versión cuando lo necesites.');
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
            'subdepartamento_accesos_id' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $departamentos = collect($accesos->departamentosActivos());
            $departamento = $departamentos->firstWhere('id', $datos['departamento_accesos_id']);
        } catch (\Throwable) {
            return back()->withInput()->withErrors([
                'departamento_accesos_id' => 'No fue posible validar el departamento con Accesos. Intenta nuevamente.',
            ]);
        }

        if (! $departamento || $departamento['parent_id'] !== null) {
            return back()->withInput()->withErrors([
                'departamento_accesos_id' => 'Selecciona un departamento válido de la lista proporcionada por Accesos.',
            ]);
        }

        $subdepartamento = null;

        if (! empty($datos['subdepartamento_accesos_id'])) {
            $subdepartamento = $departamentos->firstWhere('id', $datos['subdepartamento_accesos_id']);

            if (! $subdepartamento || $subdepartamento['parent_id'] !== $departamento['id']) {
                return back()->withInput()->withErrors([
                    'subdepartamento_accesos_id' => 'Selecciona un subdepartamento válido para el departamento elegido.',
                ]);
            }
        }

        $estadoInicial = FormularioEstado::query()
            ->where('clave', 'BORRADOR')
            ->where('activo', true)
            ->firstOrFail();

        $formulario = DB::transaction(function () use ($request, $datos, $departamento, $subdepartamento, $estadoInicial): Formulario {
            $creadoPor = session('accesos.id');
            $formulario = Formulario::create([
                'id' => (string) Str::uuid(),
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'] ?? null,
                'departamento_accesos_id' => $departamento['id'],
                'departamento_nombre_snapshot' => $departamento['nombre'],
                'subdepartamento_accesos_id' => $subdepartamento['id'] ?? null,
                'subdepartamento_nombre_snapshot' => $subdepartamento['nombre'] ?? null,
                'creado_por_accesos_id' => $creadoPor,
            ]);

            $version = $formulario->versiones()->create([
                'id' => (string) Str::uuid(),
                'formulario_estado_id' => $estadoInicial->id,
                'numero_version' => 1,
                'creado_por_accesos_id' => $creadoPor,
            ]);

            Auditoria::registrar($request, 'FORMULARIO_CREADO', 'FORMULARIO', $formulario->id, null, $formulario->only(['nombre', 'descripcion', 'departamento_accesos_id', 'departamento_nombre_snapshot', 'subdepartamento_accesos_id', 'subdepartamento_nombre_snapshot']));

            return $formulario;
        });

        return redirect()
            ->route('formulario.edit', $formulario)
            ->with('success', 'El formulario se creó como borrador. Continúa agregando sus secciones y campos.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Formulario $formulario)
    {
        $version = $formulario->ultimaVersion()
            ->with(['estado', 'tramites', 'secciones.campos.opciones', 'secciones.campos.gruposCampos', 'secciones.campos.condicionesDestino.campoOrigen', 'gruposCampos.campos', 'gruposCampos.condicionesDestino.campoOrigen'])
            ->firstOrFail();

        return view('formularios.show', compact('formulario', 'version'));
    }

    /**
     * Muestra una versión histórica del formulario en modo de solo lectura.
     */
    public function showVersion(Formulario $formulario, FormularioVersion $version)
    {
        abort_unless($version->formulario_id === $formulario->id, 404);

        $version->load([
            'estado',
            'tramites',
            'secciones.campos.opciones',
            'secciones.campos.gruposCampos',
            'secciones.campos.condicionesDestino.campoOrigen',
            'gruposCampos.campos',
            'gruposCampos.condicionesDestino.campoOrigen',
        ]);

        $esVersionActual = $formulario->ultimaVersion()->value('id') === $version->id;

        return view('formularios.show', compact('formulario', 'version', 'esVersionActual'));
    }

    /**
     * Crea un borrador con el contenido de una versión histórica, sin modificarla.
     */
    public function storeVersionDesde(Request $request, Formulario $formulario, FormularioVersion $version)
    {
        abort_unless($version->formulario_id === $formulario->id, 404);

        if ($formulario->versiones()->whereHas('estado', fn ($estado) => $estado->where('clave', 'BORRADOR'))->exists()) {
            return back()->withErrors(['version' => 'Ya existe una versión en borrador. Termínala y publícala antes de restaurar otra versión.']);
        }

        $estadoBorrador = FormularioEstado::query()
            ->where('clave', 'BORRADOR')
            ->where('activo', true)
            ->firstOrFail();

        $nuevaVersion = DB::transaction(function () use ($request, $formulario, $version, $estadoBorrador): FormularioVersion {
            $numeroVersion = ((int) DB::table('formulario_versiones')
                ->where('formulario_id', $formulario->id)
                ->lockForUpdate()
                ->max('numero_version')) + 1;

            $nuevaVersion = $formulario->versiones()->create([
                'id' => (string) Str::uuid(),
                'formulario_estado_id' => $estadoBorrador->id,
                'numero_version' => $numeroVersion,
                'creado_por_accesos_id' => session('accesos.id'),
            ]);

            $this->copiarContenidoVersion($version, $nuevaVersion);

            Auditoria::registrar(
                $request,
                'FORMULARIO_VERSION_RESTAURADA',
                'FORMULARIO_VERSION',
                $nuevaVersion->id,
                ['version_origen_id' => $version->id, 'numero_version_origen' => $version->numero_version],
                ['numero_version' => $numeroVersion, 'estado' => 'BORRADOR'],
            );

            return $nuevaVersion;
        });

        return redirect()->route('formulario.edit', $formulario)
            ->with('success', 'Se creó la versión '.$nuevaVersion->numero_version.' en borrador a partir de la versión '.$version->numero_version.'. Revísala y publícala cuando esté lista.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Formulario $formulario, AccesosClient $accesos)
    {
        $version = $formulario->versiones()
            ->with(['estado', 'tramites', 'secciones.campos.opciones', 'secciones.campos.gruposCampos', 'secciones.campos.condicionesDestino.campoOrigen', 'gruposCampos.campos', 'gruposCampos.condicionesDestino.campoOrigen'])
            ->orderByDesc('numero_version')
            ->firstOrFail();

        try {
            $departamentos = $accesos->departamentosActivos();
        } catch (\Throwable $exception) {
            Log::warning('No se pudo cargar el catálogo de departamentos de Accesos.', ['exception' => $exception]);
            $departamentos = [];
        }

        $historialVersiones = $formulario->versiones()
            ->with('estado')
            ->orderByDesc('numero_version')
            ->get();

        return view('formularios.edit', compact('formulario', 'version', 'departamentos', 'historialVersiones'));
    }

    public function storeSeccion(Request $request, Formulario $formulario)
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
        ]);

        $version = $this->versionBorrador($formulario);
        $baseClave = Str::of($datos['titulo'])->slug('_')->value() ?: 'seccion';
        $clave = $baseClave;
        $sufijo = 2;

        while ($version->secciones()->where('clave', $clave)->exists()) {
            $clave = $baseClave.'_'.$sufijo++;
        }

        $seccion = $version->secciones()->create([
            'id' => (string) Str::uuid(),
            'clave' => $clave,
            'titulo' => $datos['titulo'],
            'descripcion' => $datos['descripcion'] ?? null,
            'orden' => $version->secciones()->count() + 1,
            'activo' => true,
        ]);
        Auditoria::registrar($request, 'FORMULARIO_SECCION_CREADA', 'FORMULARIO_SECCION', $seccion->id, null, $seccion->only(['clave', 'titulo', 'descripcion', 'orden', 'activo']));

        return redirect()->route('formulario.edit', $formulario)->with('success', 'Se agregó la sección al borrador.');
    }

    public function updateSeccion(Request $request, Formulario $formulario, FormularioSeccion $seccion)
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
        ]);

        $version = $this->versionBorrador($formulario);
        abort_unless($seccion->formulario_version_id === $version->id, 404);

        $anteriores = $seccion->only(['titulo', 'descripcion']);
        $seccion->update([
            'titulo' => $datos['titulo'],
            'descripcion' => $datos['descripcion'] ?? null,
        ]);
        Auditoria::registrar($request, 'FORMULARIO_SECCION_ACTUALIZADA', 'FORMULARIO_SECCION', $seccion->id, $anteriores, $seccion->only(['titulo', 'descripcion']));

        return redirect()->route('formulario.edit', $formulario)->with('success', 'La sección se actualizó.');
    }

    public function storeCampo(Request $request, Formulario $formulario, FormularioSeccion $seccion)
    {
        $datos = $request->validate([
            'etiqueta' => ['required', 'string', 'max:200'],
            'tipo' => ['required', Rule::in(array_keys(config('formulario.tipos_campo')))],
            'requerido' => ['nullable', 'boolean'],
            'opciones' => ['nullable', 'string', 'max:5000'],
        ]);

        $opciones = collect(preg_split('/\R/u', $datos['opciones'] ?? ''))
            ->map(fn (string $opcion) => trim($opcion))
            ->filter()
            ->unique()
            ->values();

        if (in_array($datos['tipo'], config('formulario.tipos_con_opciones'), true) && $opciones->isEmpty()) {
            return back()->withInput()->withErrors([
                'opciones' => 'Agrega al menos una opción para este tipo de campo.',
            ]);
        }

        $version = $this->versionBorrador($formulario);
        abort_unless($seccion->formulario_version_id === $version->id, 404);

        $baseClave = Str::of($datos['etiqueta'])->slug('_')->value() ?: 'campo';
        $clave = $baseClave;
        $sufijo = 2;

        while ($seccion->campos()->where('clave', $clave)->exists()) {
            $clave = $baseClave.'_'.$sufijo++;
        }

        DB::transaction(function () use ($datos, $opciones, $request, $seccion, $clave): void {
            $campo = $seccion->campos()->create([
                'id' => (string) Str::uuid(),
                'clave' => $clave,
                'etiqueta' => $datos['etiqueta'],
                'tipo' => $datos['tipo'],
                'requerido' => $request->boolean('requerido'),
                'orden' => $seccion->campos()->count() + 1,
                'activo' => true,
            ]);

            $opciones->each(function (string $opcion, int $indice) use ($campo): void {
                $campo->opciones()->create([
                    'id' => (string) Str::uuid(),
                    'valor' => (string) ($indice + 1),
                    'etiqueta' => $opcion,
                    'orden' => $indice + 1,
                    'activo' => true,
                ]);
            });

            Auditoria::registrar($request, 'FORMULARIO_CAMPO_CREADO', 'FORMULARIO_CAMPO', $campo->id, null, $campo->only(['clave', 'etiqueta', 'tipo', 'requerido', 'orden', 'activo']) + ['opciones' => $opciones->all()]);
        });

        return redirect()->route('formulario.edit', $formulario)->with('success', 'Se agregó el campo al borrador.');
    }

    public function destroyCampo(Request $request, Formulario $formulario, FormularioSeccion $seccion, FormularioCampo $campo)
    {
        $version = $this->versionBorrador($formulario);
        abort_unless($seccion->formulario_version_id === $version->id && $campo->seccion_id === $seccion->id, 404);

        if (DB::table('solicitud_respuestas')->where('campo_id', $campo->id)->exists()) {
            return redirect()->route('formulario.edit', $formulario)->withErrors([
                'campo' => 'No se puede eliminar un campo que ya tiene respuestas registradas.',
            ]);
        }

        $anteriores = $campo->only(['clave', 'etiqueta', 'tipo', 'requerido', 'orden', 'activo']);
        DB::transaction(function () use ($request, $campo, $anteriores): void {
            $campo->delete();
            Auditoria::registrar($request, 'FORMULARIO_CAMPO_ELIMINADO', 'FORMULARIO_CAMPO', $campo->id, $anteriores, null);
        });

        return redirect()->route('formulario.edit', $formulario)->with('success', 'El campo se eliminó del borrador.');
    }

    public function destroySeccion(Request $request, Formulario $formulario, FormularioSeccion $seccion)
    {
        $version = $this->versionBorrador($formulario);
        abort_unless($seccion->formulario_version_id === $version->id, 404);
        $campoIds = $seccion->campos()->pluck('id');

        if (DB::table('solicitud_respuestas')->whereIn('campo_id', $campoIds)->exists()) {
            return redirect()->route('formulario.edit', $formulario)->withErrors(['seccion' => 'No se puede eliminar una sección que contiene respuestas registradas.']);
        }

        if (DB::table('formulario_grupo_campo')->whereIn('campo_id', $campoIds)->exists()) {
            return redirect()->route('formulario.edit', $formulario)->withErrors(['seccion' => 'Primero retira del grupo los campos de esta sección.']);
        }

        $anteriores = ['seccion' => $seccion->only(['clave', 'titulo', 'descripcion', 'orden', 'activo']), 'campos' => $seccion->campos()->get(['id', 'clave', 'etiqueta', 'tipo', 'requerido', 'orden', 'activo'])->toArray()];
        DB::transaction(function () use ($request, $seccion, $version, $anteriores): void {
            $seccion->delete();
            $version->secciones()->orderBy('orden')->get()->each(fn (FormularioSeccion $item, int $indice) => $item->update(['orden' => $indice + 1]));
            Auditoria::registrar($request, 'FORMULARIO_SECCION_ELIMINADA', 'FORMULARIO_SECCION', $seccion->id, $anteriores, null);
        });

        return redirect()->route('formulario.edit', $formulario)->with('success', 'La sección se eliminó.');
    }

    public function ordenarCampos(Request $request, Formulario $formulario)
    {
        $datos = $request->validate([
            'campos' => ['required', 'array'],
            'campos.*.id' => ['required', 'uuid'],
            'campos.*.seccion_id' => ['required', 'uuid'],
            'campos.*.orden' => ['required', 'integer', 'min:1'],
        ]);
        $version = $this->versionBorrador($formulario);
        $ids = collect($datos['campos'])->pluck('id');

        abort_if($ids->unique()->count() !== $ids->count(), 422, 'Una pregunta no puede aparecer dos veces.');

        $campos = FormularioCampo::query()->whereIn('id', $ids)
            ->whereHas('seccion', fn ($query) => $query->where('formulario_version_id', $version->id))
            ->get()->keyBy('id');
        $seccionIds = collect($datos['campos'])->pluck('seccion_id')->unique();
        $seccionesValidas = $version->secciones()->whereIn('id', $seccionIds)->count();
        $totalCampos = FormularioCampo::query()->whereHas('seccion', fn ($query) => $query->where('formulario_version_id', $version->id))->count();
        abort_unless($campos->count() === $ids->count() && $ids->count() === $totalCampos && $seccionesValidas === $seccionIds->count(), 422);

        DB::transaction(function () use ($request, $datos, $campos, $ids, $version): void {
            $clavesOriginales = $campos->mapWithKeys(fn (FormularioCampo $campo) => [$campo->id => $campo->clave]);
            $campos->each(fn (FormularioCampo $campo) => $campo->update(['clave' => 'moviendo_'.str_replace('-', '', $campo->id)]));
            $clavesPorSeccion = [];

            foreach (collect($datos['campos'])->pluck('seccion_id')->unique() as $seccionId) {
                $clavesPorSeccion[$seccionId] = DB::table('formulario_campos')->where('seccion_id', $seccionId)->whereNotIn('id', $ids)->pluck('clave')->all();
            }

            foreach ($datos['campos'] as $dato) {
                $campo = $campos->get($dato['id']);
                $baseClave = $clavesOriginales->get($campo->id);
                $clave = $baseClave;
                $sufijo = 2;
                while (in_array($clave, $clavesPorSeccion[$dato['seccion_id']], true)) {
                    $clave = $baseClave.'_'.$sufijo++;
                }
                $clavesPorSeccion[$dato['seccion_id']][] = $clave;
                $campo->update(['seccion_id' => $dato['seccion_id'], 'orden' => $dato['orden'], 'clave' => $clave]);
            }

            Auditoria::registrar($request, 'FORMULARIO_CAMPOS_ORDENADOS', 'FORMULARIO_VERSION', $version->id, null, ['campos' => collect($datos['campos'])->map(fn (array $dato) => ['campo_id' => $dato['id'], 'seccion_id' => $dato['seccion_id'], 'orden' => $dato['orden']])->all()]);
        });

        return response()->json(['message' => 'El orden de las preguntas se guardó.']);
    }

    public function updateCampo(Request $request, Formulario $formulario, FormularioSeccion $seccion, FormularioCampo $campo)
    {
        $datos = $request->validate([
            'etiqueta' => ['required', 'string', 'max:200'],
            'tipo' => ['required', Rule::in(array_keys(config('formulario.tipos_campo')))],
            'requerido' => ['nullable', 'boolean'],
            'opciones' => ['nullable', 'string', 'max:5000'],
        ]);

        $version = $this->versionBorrador($formulario);
        abort_unless($seccion->formulario_version_id === $version->id && $campo->seccion_id === $seccion->id, 404);

        if (DB::table('solicitud_respuestas')->where('campo_id', $campo->id)->exists()) {
            return redirect()->route('formulario.edit', $formulario)->withErrors([
                'campo' => 'No se puede modificar una pregunta que ya tiene respuestas registradas.',
            ]);
        }

        $opciones = $this->opcionesCampo($datos['opciones'] ?? '');

        if (in_array($datos['tipo'], config('formulario.tipos_con_opciones'), true) && $opciones->isEmpty()) {
            return back()->withInput()->withErrors(['opciones' => 'Agrega al menos una opción para este tipo de campo.']);
        }

        $anteriores = $campo->only(['etiqueta', 'tipo', 'requerido']) + ['opciones' => $campo->opciones()->orderBy('orden')->pluck('etiqueta')->all()];
        DB::transaction(function () use ($request, $campo, $datos, $opciones, $anteriores): void {
            $campo->update(['etiqueta' => $datos['etiqueta'], 'tipo' => $datos['tipo'], 'requerido' => $request->boolean('requerido')]);
            $campo->opciones()->delete();

            if (in_array($datos['tipo'], config('formulario.tipos_con_opciones'), true)) {
                $opciones->each(function (string $opcion, int $indice) use ($campo): void {
                    $campo->opciones()->create(['id' => (string) Str::uuid(), 'valor' => (string) ($indice + 1), 'etiqueta' => $opcion, 'orden' => $indice + 1, 'activo' => true]);
                });
            }

            Auditoria::registrar($request, 'FORMULARIO_CAMPO_ACTUALIZADO', 'FORMULARIO_CAMPO', $campo->id, $anteriores, $campo->only(['etiqueta', 'tipo', 'requerido']) + ['opciones' => $opciones->all()]);
        });

        return redirect()->route('formulario.edit', $formulario)->with('success', 'La pregunta se actualizó.');
    }

    public function moverCampo(Request $request, Formulario $formulario, FormularioSeccion $seccion, FormularioCampo $campo)
    {
        $datos = $request->validate(['direccion' => ['required', 'in:subir,bajar']]);
        $version = $this->versionBorrador($formulario);
        abort_unless($seccion->formulario_version_id === $version->id && $campo->seccion_id === $seccion->id, 404);
        $operador = $datos['direccion'] === 'subir' ? '<' : '>';
        $orden = $datos['direccion'] === 'subir' ? 'desc' : 'asc';
        $vecino = $seccion->campos()->where('orden', $operador, $campo->orden)->orderBy('orden', $orden)->first();

        if ($vecino) {
            DB::transaction(function () use ($request, $campo, $vecino, $datos): void {
                $anterior = $campo->orden;
                [$campo->orden, $vecino->orden] = [$vecino->orden, $campo->orden];
                $campo->save();
                $vecino->save();
                Auditoria::registrar($request, 'FORMULARIO_CAMPO_MOVIDO', 'FORMULARIO_CAMPO', $campo->id, ['orden' => $anterior], ['orden' => $campo->orden, 'direccion' => $datos['direccion'], 'campo_vecino_id' => $vecino->id]);
            });
        }

        return redirect()->route('formulario.edit', $formulario)->with('success', 'Se actualizó el orden de la pregunta.');
    }

    public function moverSeccion(Request $request, Formulario $formulario, FormularioSeccion $seccion)
    {
        $datos = $request->validate(['direccion' => ['required', 'in:subir,bajar']]);
        $version = $this->versionBorrador($formulario);
        abort_unless($seccion->formulario_version_id === $version->id, 404);
        $operador = $datos['direccion'] === 'subir' ? '<' : '>';
        $orden = $datos['direccion'] === 'subir' ? 'desc' : 'asc';
        $vecino = $version->secciones()->where('orden', $operador, $seccion->orden)->orderBy('orden', $orden)->first();

        if ($vecino) {
            DB::transaction(function () use ($request, $seccion, $vecino, $datos): void {
                $anterior = $seccion->orden;
                [$seccion->orden, $vecino->orden] = [$vecino->orden, $seccion->orden];
                $seccion->save();
                $vecino->save();
                Auditoria::registrar($request, 'FORMULARIO_SECCION_MOVIDA', 'FORMULARIO_SECCION', $seccion->id, ['orden' => $anterior], ['orden' => $seccion->orden, 'direccion' => $datos['direccion'], 'seccion_vecina_id' => $vecino->id]);
            });
        }

        return redirect()->route('formulario.edit', $formulario)->with('success', 'Se actualizó el orden de la sección.');
    }

    public function storeCondicion(Request $request, Formulario $formulario, FormularioSeccion $seccion, FormularioCampo $campo)
    {
        $datos = $request->validate([
            'campo_origen_id' => ['required', 'uuid'],
            'operador' => ['required', 'in:EQUALS,NOT_EQUALS,CONTAINS,GREATER_THAN,LESS_THAN,IS_EMPTY,IS_NOT_EMPTY'],
            'valor' => ['nullable', 'string', 'max:200'],
            'accion' => ['required', 'in:SHOW,HIDE,REQUIRE,OPTIONAL'],
        ]);

        $version = $this->versionBorrador($formulario);
        abort_unless($seccion->formulario_version_id === $version->id && $campo->seccion_id === $seccion->id, 404);

        $campoOrigen = FormularioCampo::query()
            ->where('id', $datos['campo_origen_id'])
            ->whereHas('seccion', fn ($query) => $query->where('formulario_version_id', $version->id))
            ->firstOrFail();

        if ($campoOrigen->id === $campo->id) {
            return back()->withInput()->withErrors([
                'campo_origen_id' => 'El campo origen debe ser distinto del campo condicionado.',
            ]);
        }

        if (! in_array($datos['operador'], ['IS_EMPTY', 'IS_NOT_EMPTY'], true) && blank($datos['valor'] ?? null)) {
            return back()->withInput()->withErrors([
                'valor' => 'Indica el valor para evaluar la condición.',
            ]);
        }

        $condicion = $campo->condicionesDestino()->create([
            'id' => (string) Str::uuid(),
            'campo_origen_id' => $campoOrigen->id,
            'operador' => $datos['operador'],
            'valor_comparacion' => in_array($datos['operador'], ['IS_EMPTY', 'IS_NOT_EMPTY'], true) ? null : ['valor' => $datos['valor']],
            'accion' => $datos['accion'],
        ]);
        Auditoria::registrar($request, 'FORMULARIO_CONDICION_CREADA', 'FORMULARIO_CONDICION', $condicion->id, null, $condicion->only(['campo_destino_id', 'campo_origen_id', 'operador', 'valor_comparacion', 'accion']));

        return redirect()->route('formulario.edit', $formulario)->with('success', 'Se agregó la condición al campo.');
    }

    public function storeGrupoCampos(Request $request, Formulario $formulario)
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:200'],
            'campo_ids' => ['required', 'array', 'min:1'],
            'campo_ids.*' => ['uuid'],
        ]);

        $version = $this->versionBorrador($formulario);
        $campos = FormularioCampo::query()
            ->whereIn('id', $datos['campo_ids'])
            ->whereHas('seccion', fn ($query) => $query->where('formulario_version_id', $version->id))
            ->get();

        if ($campos->count() !== count(array_unique($datos['campo_ids']))) {
            return back()->withInput()->withErrors(['campo_ids' => 'Selecciona únicamente campos de esta versión.']);
        }

        $camposAgrupados = FormularioCampo::query()
            ->whereIn('id', $campos->pluck('id'))
            ->whereHas('gruposCampos')
            ->pluck('etiqueta');

        if ($camposAgrupados->isNotEmpty()) {
            return back()->withInput()->withErrors([
                'campo_ids' => 'Estos campos ya pertenecen a otro grupo: '.$camposAgrupados->join(', ').'. Retíralos de ese grupo antes de continuar.',
            ]);
        }

        DB::transaction(function () use ($request, $datos, $version, $campos): void {
            $grupo = $version->gruposCampos()->create([
                'id' => (string) Str::uuid(),
                'titulo' => $datos['titulo'],
                'orden' => $version->gruposCampos()->count() + 1,
                'activo' => true,
            ]);

            $grupo->campos()->attach($campos->mapWithKeys(fn ($campo) => [
                $campo->id => ['id' => (string) Str::uuid()],
            ])->all());

            Auditoria::registrar($request, 'FORMULARIO_GRUPO_CREADO', 'FORMULARIO_GRUPO', $grupo->id, null, ['titulo' => $grupo->titulo, 'orden' => $grupo->orden, 'campos' => $campos->pluck('id')->all()]);
        });

        return redirect()->route('formulario.edit', $formulario)->with('success', 'Se creó el grupo de campos.');
    }

    public function destroyCampoGrupo(Request $request, Formulario $formulario, FormularioGrupoCampo $grupo, FormularioCampo $campo)
    {
        $version = $this->versionBorrador($formulario);
        abort_unless($grupo->formulario_version_id === $version->id, 404);
        abort_unless($grupo->campos()->whereKey($campo->id)->exists(), 404);

        $grupo->campos()->detach($campo->id);
        Auditoria::registrar($request, 'FORMULARIO_GRUPO_CAMPO_ELIMINADO', 'FORMULARIO_GRUPO', $grupo->id, ['campo_id' => $campo->id, 'campo_etiqueta' => $campo->etiqueta, 'grupo_titulo' => $grupo->titulo], null);

        return redirect()->route('formulario.edit', $formulario)->with('success', 'El campo se retiró del grupo.');
    }

    public function destroyCondicion(Request $request, Formulario $formulario, CampoCondicion $condicion)
    {
        $version = $this->versionBorrador($formulario);
        $perteneceAVersion = $condicion->campoDestino()
            ->whereHas('seccion', fn ($query) => $query->where('formulario_version_id', $version->id))
            ->exists()
            || $condicion->grupoDestino()->where('formulario_version_id', $version->id)->exists();

        abort_unless($perteneceAVersion, 404);

        $anteriores = $condicion->only(['campo_destino_id', 'grupo_destino_id', 'campo_origen_id', 'operador', 'valor_comparacion', 'accion', 'grupo']);
        DB::transaction(function () use ($request, $condicion, $anteriores): void {
            $condicion->delete();
            Auditoria::registrar($request, 'FORMULARIO_CONDICION_ELIMINADA', 'FORMULARIO_CONDICION', $condicion->id, $anteriores, null);
        });

        return redirect()->route('formulario.edit', $formulario)->with('success', 'La condición se eliminó.');
    }

    public function storeCondicionGrupo(Request $request, Formulario $formulario, FormularioGrupoCampo $grupo)
    {
        $datos = $request->validate([
            'campo_origen_id' => ['required', 'uuid'],
            'operador' => ['required', 'in:EQUALS,NOT_EQUALS,CONTAINS,GREATER_THAN,LESS_THAN,IS_EMPTY,IS_NOT_EMPTY'],
            'valor' => ['nullable', 'string', 'max:200'],
            'accion' => ['required', 'in:SHOW,HIDE,REQUIRE,OPTIONAL'],
        ]);

        $version = $this->versionBorrador($formulario);
        abort_unless($grupo->formulario_version_id === $version->id, 404);

        $campoOrigen = FormularioCampo::query()
            ->where('id', $datos['campo_origen_id'])
            ->whereHas('seccion', fn ($query) => $query->where('formulario_version_id', $version->id))
            ->firstOrFail();

        if (! in_array($datos['operador'], ['IS_EMPTY', 'IS_NOT_EMPTY'], true) && blank($datos['valor'] ?? null)) {
            return back()->withInput()->withErrors(['valor' => 'Indica el valor para evaluar la condición.']);
        }

        $condicion = $grupo->condicionesDestino()->create([
            'id' => (string) Str::uuid(),
            'campo_origen_id' => $campoOrigen->id,
            'operador' => $datos['operador'],
            'valor_comparacion' => in_array($datos['operador'], ['IS_EMPTY', 'IS_NOT_EMPTY'], true) ? null : ['valor' => $datos['valor']],
            'accion' => $datos['accion'],
        ]);
        Auditoria::registrar($request, 'FORMULARIO_GRUPO_CONDICION_CREADA', 'FORMULARIO_CONDICION', $condicion->id, null, $condicion->only(['campo_destino_id', 'grupo_destino_id', 'campo_origen_id', 'operador', 'valor_comparacion', 'accion']));

        return redirect()->route('formulario.edit', $formulario)->with('success', 'Se agregó la condición al grupo.');
    }

    private function copiarContenidoVersion(FormularioVersion $origen, FormularioVersion $destino): void
    {
        $origen->loadMissing([
            'secciones.campos.opciones',
            'secciones.campos.condicionesDestino',
            'gruposCampos.campos',
            'gruposCampos.condicionesDestino',
        ]);

        $secciones = [];
        $campos = [];
        $grupos = [];

        foreach ($origen->secciones as $seccionOrigen) {
            $seccionDestino = $destino->secciones()->create([
                'id' => (string) Str::uuid(),
                'clave' => $seccionOrigen->clave,
                'titulo' => $seccionOrigen->titulo,
                'descripcion' => $seccionOrigen->descripcion,
                'orden' => $seccionOrigen->orden,
                'activo' => $seccionOrigen->activo,
            ]);
            $secciones[$seccionOrigen->id] = $seccionDestino;

            foreach ($seccionOrigen->campos as $campoOrigen) {
                $campoDestino = $seccionDestino->campos()->create([
                    'id' => (string) Str::uuid(),
                    'clave' => $campoOrigen->clave,
                    'etiqueta' => $campoOrigen->etiqueta,
                    'tipo' => $campoOrigen->tipo,
                    'requerido' => $campoOrigen->requerido,
                    'orden' => $campoOrigen->orden,
                    'configuracion' => $campoOrigen->configuracion,
                    'activo' => $campoOrigen->activo,
                ]);
                $campos[$campoOrigen->id] = $campoDestino;

                foreach ($campoOrigen->opciones as $opcionOrigen) {
                    $campoDestino->opciones()->create([
                        'id' => (string) Str::uuid(),
                        'valor' => $opcionOrigen->valor,
                        'etiqueta' => $opcionOrigen->etiqueta,
                        'orden' => $opcionOrigen->orden,
                        'activo' => $opcionOrigen->activo,
                    ]);
                }
            }
        }

        foreach ($origen->gruposCampos as $grupoOrigen) {
            $grupoDestino = $destino->gruposCampos()->create([
                'id' => (string) Str::uuid(),
                'titulo' => $grupoOrigen->titulo,
                'orden' => $grupoOrigen->orden,
                'activo' => $grupoOrigen->activo,
            ]);
            $grupos[$grupoOrigen->id] = $grupoDestino;
            $grupoDestino->campos()->attach(collect($grupoOrigen->campos)
                ->mapWithKeys(fn (FormularioCampo $campoOrigen) => [
                    $campos[$campoOrigen->id]->id => ['id' => (string) Str::uuid()],
                ])->all());
        }

        foreach ($origen->secciones as $seccionOrigen) {
            foreach ($seccionOrigen->campos as $campoOrigen) {
                foreach ($campoOrigen->condicionesDestino as $condicionOrigen) {
                    $campos[$campoOrigen->id]->condicionesDestino()->create([
                        'id' => (string) Str::uuid(),
                        'campo_origen_id' => $campos[$condicionOrigen->campo_origen_id]->id,
                        'operador' => $condicionOrigen->operador,
                        'valor_comparacion' => $condicionOrigen->valor_comparacion,
                        'accion' => $condicionOrigen->accion,
                        'grupo' => $condicionOrigen->grupo,
                    ]);
                }
            }
        }

        foreach ($origen->gruposCampos as $grupoOrigen) {
            foreach ($grupoOrigen->condicionesDestino as $condicionOrigen) {
                $grupos[$grupoOrigen->id]->condicionesDestino()->create([
                    'id' => (string) Str::uuid(),
                    'campo_origen_id' => $campos[$condicionOrigen->campo_origen_id]->id,
                    'operador' => $condicionOrigen->operador,
                    'valor_comparacion' => $condicionOrigen->valor_comparacion,
                    'accion' => $condicionOrigen->accion,
                    'grupo' => $condicionOrigen->grupo,
                ]);
            }
        }

        $requisitos = DB::table('requisito_documentos')
            ->where('formulario_version_id', $origen->id)
            ->orderBy('orden')
            ->get();

        foreach ($requisitos as $requisitoOrigen) {
            DB::table('requisito_documentos')->insert([
                'id' => (string) Str::uuid(),
                'formulario_version_id' => $destino->id,
                'clave' => $requisitoOrigen->clave,
                'nombre' => $requisitoOrigen->nombre,
                'descripcion' => $requisitoOrigen->descripcion,
                'obligatorio' => $requisitoOrigen->obligatorio,
                'formatos_permitidos' => $requisitoOrigen->formatos_permitidos,
                'tamano_maximo_mb' => $requisitoOrigen->tamano_maximo_mb,
                'cantidad_maxima' => $requisitoOrigen->cantidad_maxima,
                'orden' => $requisitoOrigen->orden,
                'condicion' => $requisitoOrigen->condicion,
                'activo' => $requisitoOrigen->activo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function opcionesCampo(string $opciones)
    {
        return collect(preg_split('/\R/u', $opciones))
            ->map(fn (string $opcion) => trim($opcion))
            ->filter()
            ->unique()
            ->values();
    }

    private function versionBorrador(Formulario $formulario)
    {
        return $formulario->versiones()
            ->whereHas('estado', fn ($query) => $query->where('clave', 'BORRADOR'))
            ->orderByDesc('numero_version')
            ->firstOrFail();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Formulario $formulario, AccesosClient $accesos)
    {
        $version = $this->versionBorrador($formulario);
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'departamento_accesos_id' => ['required', 'string', 'max:100'],
            'subdepartamento_accesos_id' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $departamentos = collect($accesos->departamentosActivos());
            $departamento = $departamentos->firstWhere('id', $datos['departamento_accesos_id']);
        } catch (\Throwable) {
            return back()->withInput()->withErrors(['departamento_accesos_id' => 'No fue posible validar el departamento con Accesos. Intenta nuevamente.']);
        }

        if (! $departamento || $departamento['parent_id'] !== null) {
            return back()->withInput()->withErrors(['departamento_accesos_id' => 'Selecciona un departamento válido de la lista.']);
        }

        $subdepartamento = null;
        if (! empty($datos['subdepartamento_accesos_id'])) {
            $subdepartamento = $departamentos->firstWhere('id', $datos['subdepartamento_accesos_id']);
            if (! $subdepartamento || $subdepartamento['parent_id'] !== $departamento['id']) {
                return back()->withInput()->withErrors(['subdepartamento_accesos_id' => 'Selecciona un subdepartamento válido para el departamento elegido.']);
            }
        }

        $anteriores = $formulario->only(['nombre', 'descripcion', 'departamento_accesos_id', 'departamento_nombre_snapshot', 'subdepartamento_accesos_id', 'subdepartamento_nombre_snapshot']);
        DB::transaction(function () use ($request, $formulario, $datos, $departamento, $subdepartamento, $anteriores): void {
            $formulario->update([
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'] ?? null,
                'departamento_accesos_id' => $departamento['id'],
                'departamento_nombre_snapshot' => $departamento['nombre'],
                'subdepartamento_accesos_id' => $subdepartamento['id'] ?? null,
                'subdepartamento_nombre_snapshot' => $subdepartamento['nombre'] ?? null,
            ]);
            Auditoria::registrar($request, 'FORMULARIO_ACTUALIZADO', 'FORMULARIO', $formulario->id, $anteriores, $formulario->only(['nombre', 'descripcion', 'departamento_accesos_id', 'departamento_nombre_snapshot', 'subdepartamento_accesos_id', 'subdepartamento_nombre_snapshot']));
        });

        return redirect()->route('formulario.edit', $formulario)->with('success', 'La información general se actualizó.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Formulario $formulario)
    {
        $versiones = $formulario->versiones()->pluck('id');
        $tieneSolicitudes = DB::table('solicitudes')->whereIn('formulario_version_id', $versiones)->exists();
        $tieneVersionPublicada = $formulario->versiones()
            ->whereHas('estado', fn ($estado) => $estado->whereIn('clave', ['PUBLICADO', 'ARCHIVADO']))
            ->exists();
        $tieneVinculoActivo = DB::table('tramite_formulario')
            ->whereIn('formulario_version_id', $versiones)
            ->where('activo', true)
            ->exists();

        if ($tieneSolicitudes || $tieneVersionPublicada || $tieneVinculoActivo) {
            return back()->withErrors([
                'formulario' => 'No se puede eliminar un formulario con solicitudes, versiones publicadas o trámites vinculados. Archiva sus versiones o desvincúlalo primero.',
            ]);
        }

        $anteriores = $formulario->only(['nombre', 'descripcion', 'departamento_accesos_id', 'departamento_nombre_snapshot', 'subdepartamento_accesos_id', 'subdepartamento_nombre_snapshot']);
        DB::transaction(function () use ($request, $formulario, $anteriores): void {
            $formulario->delete();
            Auditoria::registrar($request, 'FORMULARIO_ELIMINADO', 'FORMULARIO', $formulario->id, $anteriores, null);
        });

        return redirect()->route('formulario.index')->with('success', 'El formulario se eliminó correctamente.');
    }
}
