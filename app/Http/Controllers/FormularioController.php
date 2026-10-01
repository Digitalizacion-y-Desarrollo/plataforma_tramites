<?php

namespace App\Http\Controllers;

use App\Integrations\AccesosClient;
use App\Models\CampoCondicion;
use App\Models\Formulario;
use App\Models\FormularioCampo;
use App\Models\FormularioEstado;
use App\Models\FormularioGrupoCampo;
use App\Models\FormularioSeccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FormularioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $formularios = Formulario::query()
            ->with([
                'ultimaVersion.estado',
                'ultimaVersion.tramites',
                'ultimaVersion.secciones.campos',
            ])
            ->latest()
            ->paginate(10);

        return view('formularios.index', compact('formularios'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(AccesosClient $accesos)
    {
        try {
            $departamentos = $accesos->departamentosActivos();
        } catch (\Throwable) {
            $departamentos = [];
        }

        $tramites = DB::table('tramites')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return view('formularios.create', compact('departamentos', 'tramites'));
    }

    public function createDuplicado(Formulario $formulario, AccesosClient $accesos)
    {
        try {
            $departamentos = $accesos->departamentosActivos();
        } catch (\Throwable) {
            $departamentos = [];
        }

        $tramitesConFormulario = DB::table('tramite_formulario')->pluck('tramite_id');
        $tramites = DB::table('tramites')
            ->where('activo', true)
            ->whereNotIn('id', $tramitesConFormulario)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return view('formularios.duplicar', compact('formulario', 'tramites', 'departamentos'));
    }

    public function storeDuplicado(Request $request, Formulario $formulario, AccesosClient $accesos)
    {
        $datos = $request->validate([
            'tramite_id' => [
                'required',
                Rule::exists('tramites', 'id')->where('activo', true),
                Rule::unique('tramite_formulario', 'tramite_id'),
            ],
            'departamento_accesos_id' => ['required', 'string', 'max:100'],
            'subdepartamento_accesos_id' => ['nullable', 'string', 'max:100'],
        ], [
            'tramite_id.unique' => 'El trámite seleccionado ya tiene un formulario asignado.',
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
        $requisitosOrigen = DB::table('requisito_documentos')
            ->where('formulario_version_id', $versionOrigen->id)
            ->orderBy('orden')
            ->get();
        $estadoBorrador = FormularioEstado::query()
            ->where('clave', 'BORRADOR')
            ->where('activo', true)
            ->firstOrFail();

        $duplicado = DB::transaction(function () use ($datos, $formulario, $versionOrigen, $requisitosOrigen, $estadoBorrador, $departamento, $subdepartamento): Formulario {
            DB::table('tramites')->where('id', $datos['tramite_id'])->lockForUpdate()->firstOrFail();

            abort_if(DB::table('tramite_formulario')->where('tramite_id', $datos['tramite_id'])->exists(), 422, 'El trámite seleccionado ya tiene un formulario asignado.');

            $duplicado = Formulario::create([
                'id' => (string) Str::uuid(),
                'nombre' => $formulario->nombre.' (copia)',
                'descripcion' => $formulario->descripcion,
                'departamento_accesos_id' => $departamento['id'],
                'departamento_nombre_snapshot' => $departamento['nombre'],
                'subdepartamento_accesos_id' => $subdepartamento['id'] ?? null,
                'subdepartamento_nombre_snapshot' => $subdepartamento['nombre'] ?? null,
                'creado_por_accesos_id' => null,
            ]);
            $versionDuplicada = $duplicado->versiones()->create([
                'id' => (string) Str::uuid(),
                'formulario_estado_id' => $estadoBorrador->id,
                'numero_version' => 1,
                'creado_por_accesos_id' => null,
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

            DB::table('tramite_formulario')->insert([
                'id' => (string) Str::uuid(),
                'tramite_id' => $datos['tramite_id'],
                'formulario_version_id' => $versionDuplicada->id,
                'fecha_inicio_vigencia' => now(),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $duplicado;
        });

        return redirect()->route('formulario.edit', $duplicado)
            ->with('success', 'El formulario se duplicó como borrador para el trámite seleccionado.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, AccesosClient $accesos)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'tramite_id' => [
                'required',
                Rule::exists('tramites', 'id')->where('activo', true),
                Rule::unique('tramite_formulario', 'tramite_id'),
            ],
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

        $estadoBorrador = FormularioEstado::query()
            ->where('clave', 'BORRADOR')
            ->where('activo', true)
            ->firstOrFail();

        $formulario = DB::transaction(function () use ($datos, $departamento, $subdepartamento, $estadoBorrador): Formulario {
            $formulario = Formulario::create([
                'id' => (string) Str::uuid(),
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'] ?? null,
                'departamento_accesos_id' => $departamento['id'],
                'departamento_nombre_snapshot' => $departamento['nombre'],
                'subdepartamento_accesos_id' => $subdepartamento['id'] ?? null,
                'subdepartamento_nombre_snapshot' => $subdepartamento['nombre'] ?? null,
                'creado_por_accesos_id' => null,
            ]);

            $version = $formulario->versiones()->create([
                'id' => (string) Str::uuid(),
                'formulario_estado_id' => $estadoBorrador->id,
                'numero_version' => 1,
                'creado_por_accesos_id' => null,
            ]);

            DB::table('tramite_formulario')->insert([
                'id' => (string) Str::uuid(),
                'tramite_id' => $datos['tramite_id'],
                'formulario_version_id' => $version->id,
                'fecha_inicio_vigencia' => now(),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

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
        } catch (\Throwable) {
            $departamentos = [];
        }

        $tramites = DB::table('tramites')->where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);

        return view('formularios.edit', compact('formulario', 'version', 'departamentos', 'tramites'));
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

        $version->secciones()->create([
            'id' => (string) Str::uuid(),
            'clave' => $clave,
            'titulo' => $datos['titulo'],
            'descripcion' => $datos['descripcion'] ?? null,
            'orden' => $version->secciones()->count() + 1,
            'activo' => true,
        ]);

        return redirect()->route('formulario.edit', $formulario)->with('success', 'Se agregó la sección al borrador.');
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
        });

        return redirect()->route('formulario.edit', $formulario)->with('success', 'Se agregó el campo al borrador.');
    }

    public function destroyCampo(Formulario $formulario, FormularioSeccion $seccion, FormularioCampo $campo)
    {
        $version = $this->versionBorrador($formulario);
        abort_unless($seccion->formulario_version_id === $version->id && $campo->seccion_id === $seccion->id, 404);

        if (DB::table('solicitud_respuestas')->where('campo_id', $campo->id)->exists()) {
            return redirect()->route('formulario.edit', $formulario)->withErrors([
                'campo' => 'No se puede eliminar un campo que ya tiene respuestas registradas.',
            ]);
        }

        $campo->delete();

        return redirect()->route('formulario.edit', $formulario)->with('success', 'El campo se eliminó del borrador.');
    }

    public function destroySeccion(Formulario $formulario, FormularioSeccion $seccion)
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

        DB::transaction(function () use ($seccion, $version): void {
            $seccion->delete();
            $version->secciones()->orderBy('orden')->get()->each(fn (FormularioSeccion $item, int $indice) => $item->update(['orden' => $indice + 1]));
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

        DB::transaction(function () use ($datos, $campos, $ids): void {
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

        DB::transaction(function () use ($campo, $datos, $opciones, $request): void {
            $campo->update(['etiqueta' => $datos['etiqueta'], 'tipo' => $datos['tipo'], 'requerido' => $request->boolean('requerido')]);
            $campo->opciones()->delete();

            if (in_array($datos['tipo'], config('formulario.tipos_con_opciones'), true)) {
                $opciones->each(function (string $opcion, int $indice) use ($campo): void {
                    $campo->opciones()->create(['id' => (string) Str::uuid(), 'valor' => (string) ($indice + 1), 'etiqueta' => $opcion, 'orden' => $indice + 1, 'activo' => true]);
                });
            }
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
            DB::transaction(function () use ($campo, $vecino): void {
                [$campo->orden, $vecino->orden] = [$vecino->orden, $campo->orden];
                $campo->save();
                $vecino->save();
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
            DB::transaction(function () use ($seccion, $vecino): void {
                [$seccion->orden, $vecino->orden] = [$vecino->orden, $seccion->orden];
                $seccion->save();
                $vecino->save();
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

        $campo->condicionesDestino()->create([
            'id' => (string) Str::uuid(),
            'campo_origen_id' => $campoOrigen->id,
            'operador' => $datos['operador'],
            'valor_comparacion' => in_array($datos['operador'], ['IS_EMPTY', 'IS_NOT_EMPTY'], true) ? null : ['valor' => $datos['valor']],
            'accion' => $datos['accion'],
        ]);

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

        DB::transaction(function () use ($datos, $version, $campos): void {
            $grupo = $version->gruposCampos()->create([
                'id' => (string) Str::uuid(),
                'titulo' => $datos['titulo'],
                'orden' => $version->gruposCampos()->count() + 1,
                'activo' => true,
            ]);

            $grupo->campos()->attach($campos->mapWithKeys(fn ($campo) => [
                $campo->id => ['id' => (string) Str::uuid()],
            ])->all());
        });

        return redirect()->route('formulario.edit', $formulario)->with('success', 'Se creó el grupo de campos.');
    }

    public function destroyCampoGrupo(Formulario $formulario, FormularioGrupoCampo $grupo, FormularioCampo $campo)
    {
        $version = $this->versionBorrador($formulario);
        abort_unless($grupo->formulario_version_id === $version->id, 404);
        abort_unless($grupo->campos()->whereKey($campo->id)->exists(), 404);

        $grupo->campos()->detach($campo->id);

        return redirect()->route('formulario.edit', $formulario)->with('success', 'El campo se retiró del grupo.');
    }

    public function destroyCondicion(Formulario $formulario, CampoCondicion $condicion)
    {
        $version = $this->versionBorrador($formulario);
        $perteneceAVersion = $condicion->campoDestino()
            ->whereHas('seccion', fn ($query) => $query->where('formulario_version_id', $version->id))
            ->exists()
            || $condicion->grupoDestino()->where('formulario_version_id', $version->id)->exists();

        abort_unless($perteneceAVersion, 404);

        $condicion->delete();

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

        $grupo->condicionesDestino()->create([
            'id' => (string) Str::uuid(),
            'campo_origen_id' => $campoOrigen->id,
            'operador' => $datos['operador'],
            'valor_comparacion' => in_array($datos['operador'], ['IS_EMPTY', 'IS_NOT_EMPTY'], true) ? null : ['valor' => $datos['valor']],
            'accion' => $datos['accion'],
        ]);

        return redirect()->route('formulario.edit', $formulario)->with('success', 'Se agregó la condición al grupo.');
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
            'tramite_id' => [
                'required',
                Rule::exists('tramites', 'id')->where('activo', true),
                Rule::unique('tramite_formulario', 'tramite_id')->ignore($version->id, 'formulario_version_id'),
            ],
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

        DB::transaction(function () use ($formulario, $version, $datos, $departamento, $subdepartamento): void {
            $formulario->update([
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'] ?? null,
                'departamento_accesos_id' => $departamento['id'],
                'departamento_nombre_snapshot' => $departamento['nombre'],
                'subdepartamento_accesos_id' => $subdepartamento['id'] ?? null,
                'subdepartamento_nombre_snapshot' => $subdepartamento['nombre'] ?? null,
            ]);

            DB::table('tramite_formulario')->where('formulario_version_id', $version->id)->update([
                'tramite_id' => $datos['tramite_id'],
                'updated_at' => now(),
            ]);
        });

        return redirect()->route('formulario.edit', $formulario)->with('success', 'La información general se actualizó.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Formulario $formulario)
    {
        $formulario->delete();

        return redirect()->route('formulario.index')->with('success', 'El formulario se eliminó correctamente.');
    }
}
