<?php

namespace App\Http\Controllers;

use App\Mail\SolicitudAprobadaMail;
use App\Mail\SolicitudComentarioMail;
use App\Mail\SolicitudCorreccionDocumentosMail;
use App\Mail\SolicitudRechazadaMail;
use App\Mail\SolicitudReferenciaPagoMail;
use App\Models\ConfiguracionGeneral;
use App\Models\EstadoSolicitud;
use App\Models\FormularioCampo;
use App\Models\FormularioSeccion;
use App\Models\Solicitud;
use App\Models\SolicitudDocumentoEmitido;
use App\Models\SolicitudPago;
use App\Models\SolicitudPagoFicha;
use App\Models\Tramite;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SolicitudController extends Controller
{
    private const ESTADOS_EDITABLES = ['BORRADOR', 'ENVIADA', 'CORRECCION_SOLICITADA', 'VALIDADA_PARA_PAGO', 'EN_ESPERA_DE_PAGO', 'EN_REVISION_FINAL'];

    public function index(Request $request): View
    {
        $dependencia = $request->attributes->get('dependencia');
        $nombreDependencia = $dependencia['nombre'] ?? null;
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'estado_id' => ['nullable', 'integer', 'exists:solicitud_estados,id'],
            'tramite_id' => ['nullable', 'integer', 'exists:tramites,id'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'estadistica_periodo' => ['nullable', 'in:DIA,SEMANA,MES,RANGO'],
            'estadistica_fecha_desde' => ['nullable', 'date', 'required_if:estadistica_periodo,RANGO'],
            'estadistica_fecha_hasta' => ['nullable', 'date', 'required_if:estadistica_periodo,RANGO', 'after_or_equal:estadistica_fecha_desde'],
        ]);

        $consulta = Solicitud::query()
            ->with(['estado:id,clave,nombre', 'tramite:id,clave,nombre,modalidades,descarga_expira_dias'])
            ->when($nombreDependencia, fn ($consulta, string $nombre) => $consulta->where('departamento_nombre_snapshot', $nombre))
            ->when($filtros['q'] ?? null, function ($consulta, string $busqueda): void {
                $consulta->where(function ($consulta) use ($busqueda): void {
                    $consulta->where('folio', 'like', "%{$busqueda}%")
                        ->orWhere('curp_consulta', 'like', "%{$busqueda}%")
                        ->orWhere('email_contacto', 'like', "%{$busqueda}%");
                });
            })
            ->when($filtros['estado_id'] ?? null, fn ($consulta, int $estadoId) => $consulta->where('solicitud_estado_id', $estadoId))
            ->when($filtros['tramite_id'] ?? null, fn ($consulta, int $tramiteId) => $consulta->where('tramite_id', $tramiteId))
            ->when($filtros['fecha_desde'] ?? null, fn ($consulta, string $fecha) => $consulta->whereDate('fecha_envio', '>=', $fecha))
            ->when($filtros['fecha_hasta'] ?? null, fn ($consulta, string $fecha) => $consulta->whereDate('fecha_envio', '<=', $fecha));

        $filtroEstadisticas = $this->filtroEstadisticas($filtros);
        $metricas = $this->aplicarFiltroEstadisticas(Solicitud::query()->when($nombreDependencia, fn ($consulta, string $nombre) => $consulta->where('departamento_nombre_snapshot', $nombre)), $filtroEstadisticas);
        $totalSolicitudes = (clone $metricas)->count();
        $solicitudesEnviadas = (clone $metricas)->whereHas('estado', fn ($consulta) => $consulta->where('clave', 'ENVIADA'))->count();
        $solicitudesAprobadas = (clone $metricas)->whereHas('estado', fn ($consulta) => $consulta->where('clave', 'APROBADA'))->count();
        $solicitudesRechazadas = (clone $metricas)->whereHas('estado', fn ($consulta) => $consulta->where('clave', 'RECHAZADA'))->count();

        $solicitudes = $consulta
            ->select('solicitudes.*')
            ->selectSub(
                DB::table('solicitud_observaciones as observacion')
                    ->select('observacion.actor_tipo')
                    ->whereColumn('observacion.solicitud_id', 'solicitudes.id')
                    ->where('observacion.visible_ciudadano', true)
                    ->orderByDesc('observacion.created_at')
                    ->limit(1),
                'ultimo_actor_comentario'
            )
            ->orderByDesc('fecha_envio')->orderByDesc('created_at')->paginate(15)->withQueryString();
        $estados = EstadoSolicitud::query()->where('activo', true)->orderBy('orden')->get(['id', 'clave', 'nombre']);
        $tramites = Tramite::query()
            ->when($nombreDependencia, fn ($consulta, string $nombre) => $consulta->where('departamento_nombre_snapshot', $nombre))
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $rutaEstadisticas = $dependencia ? route('dependencias.solicitudes.index', $dependencia['slug']) : route('solicitudes.index');

        return view('solicitudes.index', compact('solicitudes', 'estados', 'tramites', 'totalSolicitudes', 'solicitudesEnviadas', 'solicitudesAprobadas', 'solicitudesRechazadas', 'dependencia', 'filtroEstadisticas', 'rutaEstadisticas'));
    }

    public function indexDependencia(Request $request, string $dependencia): View
    {
        $request->attributes->set('dependencia', $this->dependencia($dependencia));

        return $this->index($request);
    }

    public function resumenDependencia(Request $request, string $dependencia): View
    {
        $dependencia = $this->dependencia($dependencia);
        $datos = $request->validate([
            'estadistica_periodo' => ['nullable', 'in:DIA,SEMANA,MES,RANGO'],
            'estadistica_fecha_desde' => ['nullable', 'date', 'required_if:estadistica_periodo,RANGO'],
            'estadistica_fecha_hasta' => ['nullable', 'date', 'required_if:estadistica_periodo,RANGO', 'after_or_equal:estadistica_fecha_desde'],
        ]);
        $filtroEstadisticas = $this->filtroEstadisticas($datos);
        $consulta = $this->aplicarFiltroEstadisticas(Solicitud::query()->where('departamento_nombre_snapshot', $dependencia['nombre']), $filtroEstadisticas);

        return view('solicitudes.resumen-dependencia', [
            'dependencia' => $dependencia,
            'totalSolicitudes' => (clone $consulta)->count(),
            'solicitudesEnviadas' => (clone $consulta)->whereHas('estado', fn ($query) => $query->where('clave', 'ENVIADA'))->count(),
            'solicitudesAprobadas' => (clone $consulta)->whereHas('estado', fn ($query) => $query->where('clave', 'APROBADA'))->count(),
            'solicitudesRechazadas' => (clone $consulta)->whereHas('estado', fn ($query) => $query->where('clave', 'RECHAZADA'))->count(),
            'filtroEstadisticas' => $filtroEstadisticas,
            'rutaEstadisticas' => route('dependencias.resumen', $dependencia['slug']),
        ]);
    }

    private function filtroEstadisticas(array $datos): array
    {
        $periodo = $datos['estadistica_periodo'] ?? 'MES';

        return [
            'periodo' => $periodo,
            'fecha_desde' => $datos['estadistica_fecha_desde'] ?? null,
            'fecha_hasta' => $datos['estadistica_fecha_hasta'] ?? null,
        ];
    }

    private function aplicarFiltroEstadisticas($consulta, array $filtro)
    {
        return match ($filtro['periodo']) {
            'DIA' => $consulta->whereDate('fecha_envio', now()->toDateString()),
            'SEMANA' => $consulta->whereBetween('fecha_envio', [now()->startOfWeek(), now()->endOfWeek()]),
            'RANGO' => $consulta->whereDate('fecha_envio', '>=', $filtro['fecha_desde'])->whereDate('fecha_envio', '<=', $filtro['fecha_hasta']),
            default => $consulta->whereBetween('fecha_envio', [now()->startOfMonth(), now()->endOfMonth()]),
        };
    }

    private function dependencia(string $slug): array
    {
        $dependencias = [
            'desarrollo-urbano' => ['slug' => 'desarrollo-urbano', 'nombre' => 'DIRECCIÓN DE DESARROLLO URBANO', 'etiqueta' => 'Desarrollo Urbano'],
            'secretaria-del-ayuntamiento' => ['slug' => 'secretaria-del-ayuntamiento', 'nombre' => 'SECRETARÍA DEL H. AYUNTAMIENTO', 'etiqueta' => 'Secretaría del H. Ayuntamiento'],
        ];

        abort_unless(isset($dependencias[$slug]), 404);

        return $dependencias[$slug];
    }

    public function show(Solicitud $solicitud): View
    {
        $solicitud->load(['estado:id,clave,nombre', 'tramite:id,clave,nombre,modalidades,descarga_expira_dias', 'documentoEmitido', 'pago']);
        if ($solicitud->pago && Schema::hasTable('solicitud_pago_fichas')) {
            $solicitud->pago->load('fichaVigente');
        } elseif ($solicitud->pago) {
            $solicitud->pago->setRelation('fichaVigente', null);
        }

        $seccionesFormulario = FormularioSeccion::query()
            ->where('formulario_version_id', $solicitud->formulario_version_id)
            ->where('activo', true)
            ->with([
                'campos' => fn ($consulta) => $consulta->where('activo', true)->orderBy('orden'),
                'campos.opciones' => fn ($consulta) => $consulta->where('activo', true)->orderBy('orden'),
            ])
            ->orderBy('orden')
            ->get()
            ->keyBy('id');

        $valoresRespuestas = DB::table('solicitud_respuestas')
            ->where('solicitud_id', $solicitud->id)
            ->get(['campo_id', 'valor'])
            ->mapWithKeys(fn ($respuesta) => [$respuesta->campo_id => json_decode($respuesta->valor, true)]);

        $puedeEditarRespuestas = in_array($solicitud->estado?->clave, self::ESTADOS_EDITABLES, true);

        $respuestas = DB::table('solicitud_respuestas as respuesta')
            ->join('formulario_campos as campo', 'campo.id', '=', 'respuesta.campo_id')
            ->join('formulario_secciones as seccion', 'seccion.id', '=', 'campo.seccion_id')
            ->where('respuesta.solicitud_id', $solicitud->id)
            ->where('campo.tipo', '!=', 'FILE')
            ->orderBy('seccion.orden')
            ->orderBy('campo.orden')
            ->get(['seccion.id as seccion_id', 'seccion.titulo as seccion_titulo', 'campo.id as campo_id', 'campo.etiqueta as campo_etiqueta', 'campo.tipo as campo_tipo', 'respuesta.valor']);

        $opcionesPorCampo = DB::table('campo_opciones')
            ->whereIn('campo_id', $respuestas->pluck('campo_id')->unique()->all())
            ->orderBy('orden')
            ->get(['campo_id', 'valor', 'etiqueta'])
            ->groupBy('campo_id');

        $respuestasPorSeccion = $respuestas
            ->map(function ($respuesta) use ($opcionesPorCampo) {
                $valor = json_decode($respuesta->valor, true);

                if (! filled($valor)) {
                    return null;
                }

                $respuesta->valor = $this->formatearValor(
                    $valor,
                    $respuesta->campo_tipo,
                    $opcionesPorCampo->get($respuesta->campo_id)
                );

                return $respuesta;
            })
            ->filter()
            ->groupBy('seccion_id');

        $documentos = DB::table('solicitud_documentos as documento')
            ->join('requisito_documentos as requisito', 'requisito.id', '=', 'documento.requisito_documento_id')
            ->join('documento_estados as estado', 'estado.id', '=', 'documento.documento_estado_id')
            ->where('documento.solicitud_id', $solicitud->id)
            ->orderBy('requisito.orden')
            ->get(['documento.id', 'requisito.nombre as requisito_nombre', 'documento.nombre_original', 'documento.tamano_bytes', 'documento.observaciones', 'estado.nombre as estado_nombre'])
            ->map(function ($documento) {
                $documento->origen = 'DOCUMENTO';

                return $documento;
            });

        $archivosDeRespuestas = DB::table('solicitud_respuestas as respuesta')
            ->join('formulario_campos as campo', 'campo.id', '=', 'respuesta.campo_id')
            ->where('respuesta.solicitud_id', $solicitud->id)
            ->where('campo.tipo', 'FILE')
            ->get(['campo.id as campo_id', 'campo.etiqueta as requisito_nombre', 'respuesta.valor'])
            ->map(function ($archivo) {
                $metadata = json_decode($archivo->valor, true) ?: [];

                return (object) [
                    'id' => $archivo->campo_id,
                    'campo_id' => $archivo->campo_id,
                    'requisito_nombre' => $archivo->requisito_nombre,
                    'nombre_original' => $metadata['nombre_original'] ?? 'Archivo sin nombre',
                    'tamano_bytes' => $metadata['tamano_bytes'] ?? 0,
                    'observaciones' => null,
                    'estado_nombre' => 'Enviado',
                    'origen' => 'RESPUESTA',
                    'metadata' => $metadata,
                ];
            })
            ->filter(fn ($archivo) => $archivo->nombre_original !== 'Archivo sin nombre');

        $documentos = $documentos->concat($archivosDeRespuestas);
        $archivosCorregibles = $archivosDeRespuestas->values();

        $comentarios = DB::table('solicitud_observaciones')
            ->where('solicitud_id', $solicitud->id)
            ->where('visible_ciudadano', true)
            ->orderBy('created_at')
            ->get(['id', 'respuesta_a_id', 'texto', 'actor_tipo', 'actor_nombre_snapshot', 'created_at']);

        $origenFichaPago = $this->origenFichaPago();

        return view('solicitudes.show', compact('solicitud', 'respuestasPorSeccion', 'seccionesFormulario', 'valoresRespuestas', 'puedeEditarRespuestas', 'documentos', 'archivosCorregibles', 'comentarios', 'origenFichaPago'));
    }

    public function actualizarDatosPersonales(Request $request, Solicitud $solicitud)
    {
        $solicitud->loadMissing('estado:id,clave');
        $this->autorizarEdicionRespuestas($solicitud);

        $datos = $request->validate([
            'nombres_solicitante' => ['required', 'string', 'min:2', 'max:100'],
            'apellido_paterno_solicitante' => ['required', 'string', 'min:2', 'max:100'],
            'apellido_materno_solicitante' => ['required', 'string', 'min:2', 'max:100'],
            'curp_consulta' => ['required', 'string', 'regex:/^[A-Z]{4}[0-9]{6}[A-Z]{6}[A-Z0-9][0-9]$/i'],
            'email_contacto' => ['required', 'email', 'max:200'],
            'telefono_contacto' => ['required', 'string', 'regex:/^[0-9+()\-\s]{7,30}$/'],
        ]);

        $datos['nombres_solicitante'] = trim($datos['nombres_solicitante']);
        $datos['apellido_paterno_solicitante'] = trim($datos['apellido_paterno_solicitante']);
        $datos['apellido_materno_solicitante'] = trim($datos['apellido_materno_solicitante']);
        $datos['curp_consulta'] = Str::upper(trim($datos['curp_consulta']));
        $datos['email_contacto'] = Str::lower(trim($datos['email_contacto']));
        $datos['telefono_contacto'] = trim($datos['telefono_contacto']);
        $datos['nombre_solicitante_snapshot'] = implode(' ', array_filter([
            $datos['nombres_solicitante'],
            $datos['apellido_paterno_solicitante'],
            $datos['apellido_materno_solicitante'],
        ], fn ($valor) => filled($valor)));

        $campos = array_keys($datos);
        $actor = $this->actor($request);

        DB::transaction(function () use ($solicitud, $datos, $campos, $actor, $request) {
            $bloqueada = Solicitud::query()->with('estado:id,clave')->lockForUpdate()->findOrFail($solicitud->id);
            $this->autorizarEdicionRespuestas($bloqueada);

            $anteriores = $bloqueada->only($campos);
            $nuevos = array_intersect_key($datos, array_flip($campos));

            if ($anteriores == $nuevos) {
                return;
            }

            $bloqueada->update($nuevos);

            DB::table('auditorias')->insert([
                'id' => (string) Str::uuid(),
                'actor_accesos_id' => $actor['id'],
                'actor_nombre_snapshot' => $actor['nombre'],
                'accion' => 'SOLICITUD_DATOS_PERSONALES_EDITADOS',
                'entidad' => 'SOLICITUD',
                'entidad_id' => $bloqueada->id,
                'datos_anteriores' => json_encode($anteriores),
                'datos_nuevos' => json_encode($nuevos),
                'ip' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return redirect()->route('solicitudes.show', $solicitud)->with('success', 'Los datos de la persona solicitante se actualizaron correctamente.');
    }

    public function actualizarRespuestas(Request $request, Solicitud $solicitud, FormularioSeccion $seccion)
    {
        $solicitud->loadMissing('estado:id,clave');
        $this->autorizarEdicionRespuestas($solicitud);
        abort_unless($seccion->activo && $seccion->formulario_version_id === $solicitud->formulario_version_id, 404);

        $campos = $seccion->campos()
            ->where('activo', true)
            ->where('tipo', '!=', 'FILE')
            ->with(['opciones' => fn ($consulta) => $consulta->where('activo', true)->orderBy('orden')])
            ->orderBy('orden')
            ->get();

        $reglas = [];
        $mensajes = [];
        foreach ($campos as $campo) {
            $this->agregarReglasEdicion($campo, $reglas, $mensajes);
        }

        $validados = $request->validate($reglas, $mensajes);
        $respuestas = $validados['respuestas'] ?? [];
        $actor = $this->actor($request);

        DB::transaction(function () use ($solicitud, $seccion, $campos, $respuestas, $actor, $request) {
            $bloqueada = Solicitud::query()->with('estado:id,clave')->lockForUpdate()->findOrFail($solicitud->id);
            $this->autorizarEdicionRespuestas($bloqueada);

            $actuales = DB::table('solicitud_respuestas')
                ->where('solicitud_id', $bloqueada->id)
                ->whereIn('campo_id', $campos->pluck('id'))
                ->get()
                ->keyBy('campo_id');

            $anteriores = [];
            $nuevos = [];

            foreach ($campos as $campo) {
                $registro = $actuales->get($campo->id);
                $anterior = $registro ? json_decode($registro->valor, true) : null;
                $nuevo = $this->normalizarValorEdicion($campo, $respuestas[$campo->id] ?? null);

                $sinCambios = $anterior === $nuevo || (is_array($anterior) && is_array($nuevo) && $anterior == $nuevo);

                if ($sinCambios) {
                    continue;
                }

                if ($nuevo === null) {
                    if ($registro) {
                        DB::table('solicitud_respuestas')->where('id', $registro->id)->delete();
                    }
                } elseif ($registro) {
                    DB::table('solicitud_respuestas')->where('id', $registro->id)->update(['valor' => json_encode($nuevo), 'updated_at' => now()]);
                } else {
                    DB::table('solicitud_respuestas')->insert([
                        'id' => (string) Str::uuid(),
                        'solicitud_id' => $bloqueada->id,
                        'campo_id' => $campo->id,
                        'valor' => json_encode($nuevo),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $anteriores[$campo->clave] = $anterior;
                $nuevos[$campo->clave] = $nuevo;
            }

            if ($anteriores === []) {
                return;
            }

            DB::table('auditorias')->insert([
                'id' => (string) Str::uuid(),
                'actor_accesos_id' => $actor['id'],
                'actor_nombre_snapshot' => $actor['nombre'],
                'accion' => 'SOLICITUD_RESPUESTAS_EDITADAS',
                'entidad' => 'SOLICITUD',
                'entidad_id' => $bloqueada->id,
                'datos_anteriores' => json_encode(['seccion' => $seccion->clave, 'respuestas' => $anteriores]),
                'datos_nuevos' => json_encode(['seccion' => $seccion->clave, 'respuestas' => $nuevos]),
                'ip' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return redirect()->route('solicitudes.show', $solicitud)->with('success', 'Los datos de la sección se actualizaron correctamente.');
    }

    private function autorizarEdicionRespuestas(Solicitud $solicitud): void
    {
        abort_unless(in_array($solicitud->estado?->clave, self::ESTADOS_EDITABLES, true), 422, 'No se pueden editar los datos de una solicitud finalizada.');
    }

    private function agregarReglasEdicion(FormularioCampo $campo, array &$reglas, array &$mensajes): void
    {
        $clave = 'respuestas.'.$campo->id;
        $requerido = (bool) $campo->requerido;
        $base = [$requerido ? 'required' : 'nullable'];

        $mensajes[$clave.'.required'] = 'El campo "'.$campo->etiqueta.'" es obligatorio.';
        $mensajes[$clave.'.string'] = 'El campo "'.$campo->etiqueta.'" debe ser texto.';
        $mensajes[$clave.'.array'] = 'El campo "'.$campo->etiqueta.'" debe ser una lista.';
        $mensajes[$clave.'.in'] = 'Selecciona una opción válida en "'.$campo->etiqueta.'".';
        $mensajes[$clave.'.*.in'] = 'Selecciona solo opciones válidas en "'.$campo->etiqueta.'".';
        $mensajes[$clave.'.email'] = 'Captura un correo electrónico válido.';
        $mensajes[$clave.'.numeric'] = 'Captura un valor numérico en "'.$campo->etiqueta.'".';
        $mensajes[$clave.'.date'] = 'Captura una fecha válida en "'.$campo->etiqueta.'".';
        $mensajes[$clave.'.regex'] = 'El formato de "'.$campo->etiqueta.'" no es válido.';
        $mensajes[$clave.'.max'] = 'El campo "'.$campo->etiqueta.'" excede la longitud permitida.';

        switch ($campo->tipo) {
            case 'ESTADO':
                $reglas[$clave] = [...$base, 'string', Rule::in(array_keys(config('formulario.entidades_federativas')))];
                break;
            case 'EMAIL':
                $reglas[$clave] = [...$base, 'email:rfc'];
                break;
            case 'NUMBER':
                $reglas[$clave] = [...$base, 'numeric'];
                break;
            case 'DATE':
                $reglas[$clave] = [...$base, 'date'];
                break;
            case 'CURP':
                $reglas[$clave] = [...$base, 'string', 'regex:/^[A-Z]{4}[0-9]{6}[A-Z]{6}[A-Z0-9][0-9]$/i'];
                break;
            case 'RFC':
                $reglas[$clave] = [...$base, 'string', 'regex:/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/i'];
                break;
            case 'CP':
                $reglas[$clave] = [...$base, 'string', 'regex:/^[0-9]{5}$/'];
                break;
            case 'SELECT':
            case 'RADIO':
                $reglas[$clave] = [...$base, 'string', Rule::in($campo->opciones->pluck('valor')->all())];
                break;
            case 'MULTISELECT':
                $reglas[$clave] = [...$base, 'array'];
                $reglas[$clave.'.*'] = ['string', Rule::in($campo->opciones->pluck('valor')->all())];
                break;
            case 'CHECKBOX':
                $reglas[$clave] = $requerido ? ['accepted'] : ['nullable', 'boolean'];
                $mensajes[$clave.'.accepted'] = 'Debes marcar "'.$campo->etiqueta.'".';
                break;
            case 'DOMICILIO':
            case 'DOMICILIO_FUERA_NEZA':
            case 'DOMICILIO_USA':
                $this->agregarReglasDomicilio($campo, $clave, $requerido, $reglas, $mensajes);
                break;
            case 'TEXTAREA':
            default:
                $reglas[$clave] = [...$base, 'string', 'max:5000'];
        }
    }

    private function agregarReglasDomicilio(FormularioCampo $campo, string $clave, bool $requerido, array &$reglas, array &$mensajes): void
    {
        $reglas[$clave] = [$requerido ? 'required' : 'nullable', 'array'];

        $subcampos = match ($campo->tipo) {
            'DOMICILIO' => config('formulario.campos_domicilio'),
            'DOMICILIO_FUERA_NEZA' => config('formulario.campos_domicilio_fuera_neza'),
            'DOMICILIO_USA' => config('formulario.campos_domicilio_usa'),
        };

        foreach ($subcampos as $subclave => $subcampo) {
            $claveSubcampo = $clave.'.'.$subclave;

            if ($campo->tipo === 'DOMICILIO' && $subclave === 'municipio') {
                $reglas[$claveSubcampo] = ['required', 'string', 'max:100'];

                continue;
            }

            if ($campo->tipo === 'DOMICILIO' && $subclave === 'numero') {
                $reglas[$claveSubcampo] = ['nullable', 'string', 'max:50'];

                continue;
            }

            $obligatorio = $requerido && ($campo->tipo !== 'DOMICILIO_USA' || ($subcampo['requerido'] ?? true));
            $reglasSubcampo = [$obligatorio ? 'required' : 'nullable', 'string'];

            if ($subclave === 'codigo_postal') {
                $reglasSubcampo[] = $campo->tipo === 'DOMICILIO_USA' ? 'regex:/^[0-9]{5}(-[0-9]{4})?$/' : 'regex:/^[0-9]{5}$/';
            } elseif ($campo->tipo === 'DOMICILIO_USA' && $subclave === 'estado') {
                $reglasSubcampo = [$obligatorio ? 'required' : 'nullable', Rule::in(array_keys(config('formulario.estados_usa')))];
            } elseif (isset($subcampo['maxlength'])) {
                $reglasSubcampo[] = 'max:'.$subcampo['maxlength'];
            }

            $reglas[$claveSubcampo] = $reglasSubcampo;
            $mensajes[$claveSubcampo.'.required'] = 'Captura '.mb_strtolower($subcampo['etiqueta'], 'UTF-8').' del domicilio.';
            $mensajes[$claveSubcampo.'.regex'] = 'El formato de '.mb_strtolower($subcampo['etiqueta'], 'UTF-8').' no es válido.';
            $mensajes[$claveSubcampo.'.in'] = 'Selecciona un valor válido en '.mb_strtolower($subcampo['etiqueta'], 'UTF-8').'.';
            $mensajes[$claveSubcampo.'.max'] = $subcampo['etiqueta'].' excede la longitud permitida.';
        }

        $reglas[$clave.'.latitud'] = ['nullable', 'string', 'max:50'];
        $reglas[$clave.'.longitud'] = ['nullable', 'string', 'max:50'];
    }

    private function normalizarValorEdicion(FormularioCampo $campo, mixed $valor): mixed
    {
        if ($campo->tipo === 'CHECKBOX') {
            return filled($valor) ? '1' : null;
        }

        if ($campo->tipo === 'MULTISELECT') {
            $valores = collect(is_array($valor) ? $valor : [])
                ->filter(fn ($elemento) => filled($elemento))
                ->values()
                ->all();

            return $valores === [] ? null : $valores;
        }

        if (in_array($campo->tipo, ['DOMICILIO', 'DOMICILIO_FUERA_NEZA', 'DOMICILIO_USA'], true)) {
            $subcampos = match ($campo->tipo) {
                'DOMICILIO' => config('formulario.campos_domicilio'),
                'DOMICILIO_FUERA_NEZA' => config('formulario.campos_domicilio_fuera_neza'),
                'DOMICILIO_USA' => config('formulario.campos_domicilio_usa'),
            };

            $claves = array_keys($subcampos);
            if ($campo->tipo === 'DOMICILIO') {
                $claves = [...$claves, 'latitud', 'longitud'];
            }

            $limpio = [];
            foreach ($claves as $subclave) {
                $elemento = is_array($valor) ? ($valor[$subclave] ?? null) : null;
                $elemento = is_string($elemento) ? trim($elemento) : $elemento;
                $limpio[$subclave] = $elemento === '' ? null : $elemento;
            }

            if ($campo->tipo === 'DOMICILIO' && blank($limpio['municipio'] ?? null)) {
                $limpio['municipio'] = config('formulario.campos_domicilio.municipio.valor');
            }

            if ($campo->tipo === 'DOMICILIO_USA') {
                $limpio['pais'] = 'US';
            }

            return $limpio;
        }

        if (is_string($valor)) {
            $limpio = trim($valor);
            if (in_array($campo->tipo, ['CURP', 'RFC'], true)) {
                $limpio = Str::upper($limpio);
            }

            return $limpio === '' ? null : $limpio;
        }

        return $valor;
    }

    public function validarParaPago(Request $request, Solicitud $solicitud)
    {
        abort_unless($solicitud->flujo_pago === 'PAGO_POST_VALIDACION' && $solicitud->estado?->clave === 'ENVIADA', 422, 'Esta solicitud no puede validarse para pago.');
        $this->moverEstado($request, $solicitud, 'VALIDADA_PARA_PAGO', 'SOLICITUD_VALIDADA_PARA_PAGO', 'La información fue validada y no podrá revertirse.');

        return redirect()->route('solicitudes.show', $solicitud)->with('success', 'La información fue validada; ya puedes enviar la ficha de pago.');
    }

    public function enviarFichaPago(Request $request, Solicitud $solicitud)
    {
        abort_unless($this->origenFichaPago() === 'SISTEMA_EXTERNO', 422, 'La configuración actual genera la ficha de pago desde el portal.');
        $solicitud->load('estado');
        $this->autorizarFichaPago($solicitud);
        abort_if(SolicitudPago::query()->where('solicitud_id', $solicitud->id)->exists(), 422, 'La solicitud ya tiene una ficha de pago.');
        $datos = $this->validarFichaPago($request);
        $actor = $this->actor($request);
        $archivoGuardado = null;

        try {
            [$pago, $ficha, $notificacionId] = DB::transaction(function () use ($request, $solicitud, $datos, $actor, &$archivoGuardado) {
                $bloqueada = Solicitud::query()->with('estado')->lockForUpdate()->findOrFail($solicitud->id);
                $this->autorizarFichaPago($bloqueada);
                abort_if(SolicitudPago::query()->where('solicitud_id', $bloqueada->id)->exists(), 422, 'La solicitud ya tiene una ficha de pago.');

                $pago = SolicitudPago::query()->create([
                    'id' => (string) Str::uuid(),
                    'solicitud_id' => $bloqueada->id,
                    'importe' => $bloqueada->importe_pago,
                    'fecha_emision' => now(),
                    'fecha_vencimiento' => now()->addDays($bloqueada->pago_vigencia_dias),
                    'emisor_accesos_id' => $actor['id'],
                    'emisor_nombre_snapshot' => $actor['nombre'],
                ]);
                $ficha = $this->guardarFichaPago($datos['ficha'], $datos['indicaciones'], $pago, 1, null, $actor, $archivoGuardado);
                $anterior = $bloqueada->solicitud_estado_id;
                $nuevo = $this->estadoId('EN_ESPERA_DE_PAGO');
                $bloqueada->update(['solicitud_estado_id' => $nuevo]);
                $this->registrarMovimiento($bloqueada, $anterior, $nuevo, 'Ficha de pago enviada.', $actor, 'SOLICITUD_FICHA_PAGO_ENVIADA', $request);
                $notificacionId = $this->notificacionPendiente($bloqueada, 'SOLICITUD_FICHA_PAGO_ENVIADA', 'Ficha de pago · Folio '.$bloqueada->folio);

                return [$pago, $ficha, $notificacionId];
            });
        } catch (\Throwable $exception) {
            if ($archivoGuardado) {
                Storage::disk('local')->delete($archivoGuardado);
            }

            throw $exception;
        }

        $solicitud->refresh()->load('tramite');
        $this->enviarFichaPagoPorCorreo($solicitud, $ficha, $notificacionId, false);

        return redirect()->route('solicitudes.show', $solicitud)->with('success', 'La ficha de pago se envió por correo.');
    }

    public function reemplazarFichaPago(Request $request, Solicitud $solicitud)
    {
        abort_unless($this->origenFichaPago() === 'SISTEMA_EXTERNO', 422, 'La configuración actual genera la ficha de pago desde el portal.');
        $solicitud->load(['estado', 'pago']);
        $this->autorizarReemplazoFichaPago($solicitud);
        $datos = $request->validate([
            'ficha' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'indicaciones' => ['required', 'string', 'max:5000'],
            'motivo_reemplazo' => ['required', 'string', 'max:5000'],
        ]);
        $actor = $this->actor($request);
        $archivoGuardado = null;

        try {
            [$ficha, $notificacionId] = DB::transaction(function () use ($request, $solicitud, $datos, $actor, &$archivoGuardado) {
                $bloqueada = Solicitud::query()->with('estado')->lockForUpdate()->findOrFail($solicitud->id);
                $pago = SolicitudPago::query()->where('solicitud_id', $bloqueada->id)->lockForUpdate()->firstOrFail();
                $bloqueada->setRelation('pago', $pago);
                $this->autorizarReemplazoFichaPago($bloqueada);
                $fichaAnterior = SolicitudPagoFicha::query()->where('solicitud_pago_id', $pago->id)->where('es_vigente', true)->lockForUpdate()->firstOrFail();
                $fichaAnterior->update(['es_vigente' => false]);
                $version = ((int) SolicitudPagoFicha::query()->where('solicitud_pago_id', $pago->id)->max('version')) + 1;
                $ficha = $this->guardarFichaPago($datos['ficha'], $datos['indicaciones'], $pago, $version, trim($datos['motivo_reemplazo']), $actor, $archivoGuardado);
                DB::table('auditorias')->insert([
                    'id' => (string) Str::uuid(), 'actor_accesos_id' => $actor['id'], 'actor_nombre_snapshot' => $actor['nombre'],
                    'accion' => 'SOLICITUD_FICHA_PAGO_REEMPLAZADA', 'entidad' => 'SOLICITUD_PAGO_FICHA', 'entidad_id' => $ficha->id,
                    'datos_anteriores' => json_encode(['ficha_id' => $fichaAnterior->id, 'version' => $fichaAnterior->version]),
                    'datos_nuevos' => json_encode(['ficha_id' => $ficha->id, 'version' => $ficha->version, 'motivo_reemplazo' => $ficha->motivo_reemplazo]),
                    'ip' => $request->ip(), 'created_at' => now(),
                ]);
                $notificacionId = $this->notificacionPendiente($bloqueada, 'SOLICITUD_FICHA_PAGO_REEMPLAZADA', 'Ficha de pago actualizada · Folio '.$bloqueada->folio);

                return [$ficha, $notificacionId];
            });
        } catch (\Throwable $exception) {
            if ($archivoGuardado) {
                Storage::disk('local')->delete($archivoGuardado);
            }

            throw $exception;
        }

        $solicitud->refresh()->load('tramite');
        $this->enviarFichaPagoPorCorreo($solicitud, $ficha, $notificacionId, true);

        return redirect()->route('solicitudes.show', $solicitud)->with('success', 'La ficha de pago se reemplazó y envió por correo.');
    }

    public function generarReferenciaPago(Request $request, Solicitud $solicitud)
    {
        abort_unless($this->origenFichaPago() === 'GENERADA_EN_PORTAL', 422, 'La configuración actual requiere cargar una ficha de pago externa.');
        $solicitud->load(['estado', 'tramite']);
        $this->autorizarReferenciaPago($solicitud);
        $vistaPrevia = $this->vistaPreviaReferenciaPago($request, $solicitud);
        abort_unless($vistaPrevia, 422, 'Primero revisa la vista previa de la referencia de pago.');
        $permitido = $vistaPrevia !== null;
        abort_unless($permitido && $solicitud->importe_pago !== null && $solicitud->pago_vigencia_dias, 422, 'La referencia de pago no está disponible para esta solicitud.');
        $actor = $this->actor($request);
        $archivoGuardado = null;
        try {
            [$pago, $notificacionId] = DB::transaction(function () use ($request, $solicitud, $actor, $vistaPrevia, &$archivoGuardado) {
                $bloqueada = Solicitud::query()->with('estado')->lockForUpdate()->findOrFail($solicitud->id);
                abort_if(SolicitudPago::query()->where('solicitud_id', $bloqueada->id)->exists(), 422, 'La solicitud ya tiene una referencia de pago.');
                $origenValido = ($bloqueada->flujo_pago === 'PAGO_DIRECTO' && $bloqueada->estado?->clave === 'ENVIADA') || ($bloqueada->flujo_pago === 'PAGO_POST_VALIDACION' && $bloqueada->estado?->clave === 'VALIDADA_PARA_PAGO');
                abort_unless($origenValido, 422, 'La solicitud cambió de estado.');
                abort_if(SolicitudPago::query()->where('referencia', $vistaPrevia['referencia'])->exists(), 422, 'La referencia de pago ya no está disponible. Revisa una nueva vista previa.');
                $pago = new SolicitudPago(['id' => (string) Str::uuid(), 'solicitud_id' => $bloqueada->id, 'referencia' => $vistaPrevia['referencia'], 'importe' => $bloqueada->importe_pago, 'fecha_emision' => Carbon::parse($vistaPrevia['fecha_emision']), 'fecha_vencimiento' => Carbon::parse($vistaPrevia['fecha_vencimiento']), 'emisor_accesos_id' => $actor['id'], 'emisor_nombre_snapshot' => $actor['nombre']]);
                $pago->setRelation('solicitud', $bloqueada->load('tramite'));
                $contenido = Pdf::loadView('pdf.referencia-pago', ['solicitud' => $bloqueada, 'pago' => $pago])->output();
                $pago->pdf_nombre_original = 'referencia-pago-'.$bloqueada->folio.'.pdf';
                $pago->pdf_storage_key = 'solicitudes/'.$bloqueada->id.'/pagos/'.Str::uuid().'.pdf';
                $pago->pdf_hash_archivo = hash('sha256', $contenido);
                Storage::disk('local')->put($pago->pdf_storage_key, $contenido);
                $archivoGuardado = $pago->pdf_storage_key;
                $pago->save();
                $anterior = $bloqueada->solicitud_estado_id;
                $nuevo = $this->estadoId('EN_ESPERA_DE_PAGO');
                $bloqueada->update(['solicitud_estado_id' => $nuevo]);
                $this->registrarMovimiento($bloqueada, $anterior, $nuevo, 'Referencia de pago generada: '.$pago->referencia, $actor, 'SOLICITUD_REFERENCIA_PAGO_GENERADA', $request);
                $notificacionId = $this->notificacionPendiente($bloqueada, 'SOLICITUD_REFERENCIA_PAGO', 'Referencia de pago · Folio '.$bloqueada->folio);

                return [$pago, $notificacionId];
            });
        } catch (\Throwable $exception) {
            if ($archivoGuardado) {
                Storage::disk('local')->delete($archivoGuardado);
            } throw $exception;
        }
        $solicitud->refresh()->load('tramite', 'estado');
        $pago->setRelation('solicitud', $solicitud);
        $this->enviarReferenciaPago($solicitud, $pago, $notificacionId);
        $request->session()->forget($this->llaveVistaPreviaReferenciaPago($solicitud));

        return redirect()->route('solicitudes.show', $solicitud)->with('success', 'La referencia de pago se generó y envió por correo.');
    }

    public function previsualizarReferenciaPago(Request $request, Solicitud $solicitud)
    {
        abort_unless($this->origenFichaPago() === 'GENERADA_EN_PORTAL', 422, 'La configuración actual requiere cargar una ficha de pago externa.');
        $solicitud->load(['estado', 'tramite']);
        $this->autorizarReferenciaPago($solicitud);
        abort_if(SolicitudPago::query()->where('solicitud_id', $solicitud->id)->exists(), 422, 'La solicitud ya tiene una referencia de pago.');

        $vistaPrevia = $this->crearVistaPreviaReferenciaPago($request, $solicitud);
        $pago = new SolicitudPago([
            'referencia' => $vistaPrevia['referencia'],
            'importe' => $solicitud->importe_pago,
            'fecha_emision' => Carbon::parse($vistaPrevia['fecha_emision']),
            'fecha_vencimiento' => Carbon::parse($vistaPrevia['fecha_vencimiento']),
        ]);

        return Pdf::loadView('pdf.referencia-pago', compact('solicitud', 'pago'))->stream('vista-previa-referencia-pago-'.$solicitud->folio.'.pdf');
    }

    public function validarPago(Request $request, Solicitud $solicitud)
    {
        abort_unless($solicitud->estado?->clave === 'EN_ESPERA_DE_PAGO', 422, 'Solo pueden validarse pagos pendientes.');
        $actor = $this->actor($request);
        DB::transaction(function () use ($request, $solicitud, $actor) {
            $bloqueada = Solicitud::query()->with('estado')->lockForUpdate()->findOrFail($solicitud->id);
            $pago = SolicitudPago::query()->where('solicitud_id', $bloqueada->id)->lockForUpdate()->firstOrFail();
            abort_unless($bloqueada->flujo_pago !== 'PAGO_POST_VALIDACION' || $pago->tieneComprobante(), 422, 'El ciudadano aún no ha subido el comprobante de pago.');
            abort_unless($bloqueada->estado?->clave === 'EN_ESPERA_DE_PAGO' && ! $pago->fecha_pago_validado && ! $pago->vencido(), 422, 'El pago no puede validarse.');
            $pago->update(['fecha_pago_validado' => now(), 'comentario_validacion' => null, 'validador_accesos_id' => $actor['id'], 'validador_nombre_snapshot' => $actor['nombre'], 'validador_departamento_snapshot' => $actor['departamento']]);
            $anterior = $bloqueada->solicitud_estado_id;
            $nuevo = $this->estadoId('EN_REVISION_FINAL');
            $bloqueada->update(['solicitud_estado_id' => $nuevo]);
            $this->registrarMovimiento($bloqueada, $anterior, $nuevo, 'Pago validado.', $actor, 'SOLICITUD_PAGO_VALIDADO', $request);
        });

        return redirect()->route('solicitudes.show', $solicitud)->with('success', 'El pago fue validado; la solicitud pasó a revisión final.');
    }

    public function descargarComprobantePago(Solicitud $solicitud): StreamedResponse
    {
        $pago = $solicitud->pago;
        abort_unless($pago && $pago->tieneComprobante(), 404);
        abort_unless(Storage::disk('local')->exists($pago->comprobante_storage_key), 404);

        return Storage::disk('local')->download($pago->comprobante_storage_key, $pago->comprobante_nombre_original);
    }

    public function comentar(Request $request, Solicitud $solicitud)
    {
        $datos = $request->validate(['comentario' => ['required', 'string', 'max:5000']]);
        $actor = $this->actor($request);
        $texto = trim($datos['comentario']);
        $notificacionId = DB::transaction(function () use ($solicitud, $actor, $texto) {
            DB::table('solicitud_observaciones')->insert(['id' => (string) Str::uuid(), 'solicitud_id' => $solicitud->id, 'texto' => $texto, 'tipo' => 'COMENTARIO', 'visible_ciudadano' => true, 'actor_tipo' => 'SERVIDOR_PUBLICO', 'actor_accesos_id' => $actor['id'], 'actor_nombre_snapshot' => $actor['nombre'], 'created_at' => now(), 'updated_at' => now()]);

            return $this->notificacionPendiente($solicitud, 'SOLICITUD_COMENTARIO', 'Actualización de tu trámite · Folio '.$solicitud->folio);
        });
        $solicitud->load('tramite', 'estado');
        $this->enviarComentario($solicitud, $texto, $notificacionId);

        return redirect()->route('solicitudes.show', $solicitud)->with('success', 'El comentario se publicó y envió por correo.');
    }

    public function solicitarCorreccion(Request $request, Solicitud $solicitud)
    {
        $solicitud->load('estado');
        abort_unless($solicitud->estado?->clave === 'ENVIADA', 422, 'Solo pueden solicitarse correcciones de solicitudes enviadas.');

        $datos = $request->validate([
            'documentos' => ['required', 'array', 'min:1'],
            'documentos.*.motivo' => ['required', 'string', 'max:5000'],
        ]);
        $camposIds = array_keys($datos['documentos']);
        $archivos = DB::table('solicitud_respuestas as respuesta')
            ->join('formulario_campos as campo', 'campo.id', '=', 'respuesta.campo_id')
            ->where('respuesta.solicitud_id', $solicitud->id)
            ->where('campo.tipo', 'FILE')
            ->whereIn('campo.id', $camposIds)
            ->get(['campo.id as campo_id', 'campo.etiqueta', 'respuesta.valor']);
        abort_unless($archivos->count() === count($camposIds), 422, 'Selecciona únicamente documentos pertenecientes a esta solicitud.');

        $actor = [
            'id' => $request->session()->get('accesos.id'),
            'nombre' => $request->session()->get('accesos.nombre'),
            'departamento' => $request->session()->get('accesos.departamento'),
        ];
        $urlToken = Str::random(64);
        $password = Str::password(12, true, true, false);
        $documentosCorreo = [];

        $notificacionId = DB::transaction(function () use ($solicitud, $archivos, $datos, $actor, $urlToken, $password, &$documentosCorreo, $request) {
            $solicitudBloqueada = Solicitud::query()->lockForUpdate()->findOrFail($solicitud->id);
            $estadoAnterior = $solicitudBloqueada->solicitud_estado_id;
            $enviadaId = EstadoSolicitud::query()->where('clave', 'ENVIADA')->value('id');
            abort_unless($estadoAnterior === $enviadaId, 422, 'La solicitud ya fue atendida.');
            $estadoCorreccionId = EstadoSolicitud::query()->where('clave', 'CORRECCION_SOLICITADA')->where('activo', true)->value('id');
            $pendienteId = DB::table('notificacion_estados')->where('clave', 'PENDIENTE')->where('activo', true)->value('id');
            abort_unless($estadoCorreccionId && $pendienteId, 500);
            abort_if(DB::table('solicitud_correcciones')->where('solicitud_id', $solicitudBloqueada->id)->whereNull('completada_at')->whereNull('bloqueada_at')->exists(), 422, 'Ya existe una corrección pendiente para esta solicitud.');

            $correccionId = (string) Str::uuid();
            DB::table('solicitud_correcciones')->insert([
                'id' => $correccionId, 'solicitud_id' => $solicitudBloqueada->id,
                'url_token_hash' => hash('sha256', $urlToken), 'password_hash' => Hash::make($password),
                'intentos' => 0, 'actor_accesos_id' => $actor['id'], 'actor_nombre_snapshot' => $actor['nombre'],
                'actor_departamento_snapshot' => $actor['departamento'], 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($archivos as $archivo) {
                $motivo = trim($datos['documentos'][$archivo->campo_id]['motivo']);
                DB::table('solicitud_correccion_documentos')->insert([
                    'id' => (string) Str::uuid(), 'solicitud_correccion_id' => $correccionId, 'campo_id' => $archivo->campo_id,
                    'motivo' => $motivo, 'archivo_original' => $archivo->valor, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $documentosCorreo[] = ['nombre' => $archivo->etiqueta, 'motivo' => $motivo];
            }
            $solicitudBloqueada->update(['solicitud_estado_id' => $estadoCorreccionId]);
            DB::table('solicitud_historial_estados')->insert([
                'id' => (string) Str::uuid(), 'solicitud_id' => $solicitudBloqueada->id,
                'estado_anterior_id' => $estadoAnterior, 'estado_nuevo_id' => $estadoCorreccionId,
                'comentario' => 'Se solicitó la corrección de documentos.', 'actor_tipo' => 'SERVIDOR_PUBLICO',
                'actor_accesos_id' => $actor['id'], 'actor_nombre_snapshot' => $actor['nombre'],
                'actor_departamento_snapshot' => $actor['departamento'], 'fecha' => now(),
            ]);
            DB::table('auditorias')->insert([
                'id' => (string) Str::uuid(), 'actor_accesos_id' => $actor['id'], 'actor_nombre_snapshot' => $actor['nombre'],
                'accion' => 'SOLICITUD_CORRECCION_SOLICITADA', 'entidad' => 'SOLICITUD', 'entidad_id' => $solicitudBloqueada->id,
                'datos_anteriores' => json_encode(['solicitud_estado_id' => $estadoAnterior]),
                'datos_nuevos' => json_encode(['solicitud_estado_id' => $estadoCorreccionId, 'campos_ids' => $archivos->pluck('campo_id')->all()]),
                'ip' => $request->ip(), 'created_at' => now(),
            ]);
            $notificacionId = (string) Str::uuid();
            DB::table('notificacion_emails')->insert([
                'id' => $notificacionId, 'solicitud_id' => $solicitudBloqueada->id, 'notificacion_estado_id' => $pendienteId,
                'destinatario' => $solicitudBloqueada->email_contacto, 'tipo' => 'SOLICITUD_CORRECCION_DOCUMENTOS',
                'asunto' => 'Corrección de documentos requerida · Folio '.$solicitudBloqueada->folio,
                'intentos' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);

            return $notificacionId;
        });

        $solicitud->refresh()->load('tramite');
        $this->enviarSolicitudCorreccion($solicitud, $notificacionId, $documentosCorreo, route('correcciones.mostrar', $urlToken), $password);

        return redirect()->route('solicitudes.show', $solicitud)->with('success', 'La solicitud de corrección se envió a la ciudadanía.');
    }

    public function aprobar(Request $request, Solicitud $solicitud)
    {
        $solicitud->load('tramite', 'estado');
        $estadoAprobable = $solicitud->flujo_pago === 'SIN_PAGO' ? 'ENVIADA' : 'EN_REVISION_FINAL';
        abort_unless($solicitud->estado?->clave === $estadoAprobable, 422, 'La solicitud no está lista para aprobarse.');

        $modalidades = collect($solicitud->tramite?->modalidades ?? [])->unique()->values();
        $esEnLinea = $modalidades->count() === 1 && $modalidades->contains('EN_LINEA');
        $esPresencial = $modalidades->contains('PRESENCIAL');
        abort_unless($esEnLinea || $esPresencial, 422, 'Este trámite no puede aprobarse desde el portal.');

        $datos = $request->validate($esEnLinea ? [
            'documento_final' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'instrucciones' => ['nullable', 'string', 'max:5000'],
            'descarga_expira_dias' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ] : [
            'instrucciones' => ['required', 'string', 'max:5000'],
        ]);

        $actor = [
            'id' => $request->session()->get('accesos.id'),
            'nombre' => $request->session()->get('accesos.nombre'),
            'departamento' => $request->session()->get('accesos.departamento'),
        ];
        $archivoGuardado = null;

        try {
            [$notificacionId, $documentoEmitido, $instrucciones] = DB::transaction(function () use ($solicitud, $esEnLinea, $datos, $actor, &$archivoGuardado, $request) {
                $solicitudBloqueada = Solicitud::query()->lockForUpdate()->findOrFail($solicitud->id);
                $estadoAnterior = $solicitudBloqueada->solicitud_estado_id;
                $estadoAprobableId = EstadoSolicitud::query()->where('clave', $solicitud->flujo_pago === 'SIN_PAGO' ? 'ENVIADA' : 'EN_REVISION_FINAL')->value('id');
                abort_unless($estadoAnterior === $estadoAprobableId, 422, 'La solicitud ya fue atendida.');
                $aprobadaId = EstadoSolicitud::query()->where('clave', 'APROBADA')->where('activo', true)->value('id');
                $pendienteId = DB::table('notificacion_estados')->where('clave', 'PENDIENTE')->where('activo', true)->value('id');
                abort_unless($aprobadaId && $pendienteId, 500);

                $documentoEmitido = null;
                $instrucciones = null;
                if ($esEnLinea) {
                    $archivo = $datos['documento_final'];
                    $storageKey = $archivo->store('solicitudes/'.$solicitudBloqueada->id.'/emitidos', 'local');
                    $archivoGuardado = $storageKey;
                    $fechaEmision = now();
                    $expiraDias = $datos['descarga_expira_dias'] ?? DB::table('tramites')->where('id', $solicitudBloqueada->tramite_id)->value('descarga_expira_dias');
                    $documentoEmitido = SolicitudDocumentoEmitido::query()->create([
                        'id' => (string) Str::uuid(), 'solicitud_id' => $solicitudBloqueada->id,
                        'nombre_original' => $archivo->getClientOriginalName(), 'storage_key' => $storageKey,
                        'mime_type' => $archivo->getMimeType(), 'tamano_bytes' => $archivo->getSize(),
                        'hash_archivo' => hash_file('sha256', $archivo->getRealPath()),
                        'actor_accesos_id' => $actor['id'], 'actor_nombre_snapshot' => $actor['nombre'], 'fecha_emision' => $fechaEmision,
                        'fecha_expiracion' => $expiraDias ? $fechaEmision->copy()->addDays((int) $expiraDias) : null,
                    ]);
                    $indicaciones = trim($datos['instrucciones'] ?? '');
                    $instrucciones = $indicaciones !== '' ? $indicaciones : null;
                } else {
                    $instrucciones = trim($datos['instrucciones']);
                }

                DB::table('solicitud_aprobaciones')->insert([
                    'id' => (string) Str::uuid(), 'solicitud_id' => $solicitudBloqueada->id,
                    'solicitud_documento_emitido_id' => $documentoEmitido?->id, 'instrucciones' => $instrucciones,
                    'actor_accesos_id' => $actor['id'], 'actor_nombre_snapshot' => $actor['nombre'],
                    'actor_departamento_snapshot' => $actor['departamento'], 'fecha_aprobacion' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
                $solicitudBloqueada->update(['solicitud_estado_id' => $aprobadaId, 'fecha_terminacion' => now()]);
                DB::table('solicitud_historial_estados')->insert([
                    'id' => (string) Str::uuid(), 'solicitud_id' => $solicitudBloqueada->id, 'estado_anterior_id' => $estadoAnterior,
                    'estado_nuevo_id' => $aprobadaId, 'comentario' => $esEnLinea ? ('Solicitud aprobada con documento emitido.'.($instrucciones ? ' Indicaciones: '.$instrucciones : '')) : $instrucciones,
                    'actor_tipo' => 'SERVIDOR_PUBLICO', 'actor_accesos_id' => $actor['id'], 'actor_nombre_snapshot' => $actor['nombre'],
                    'actor_departamento_snapshot' => $actor['departamento'], 'fecha' => now(),
                ]);
                DB::table('auditorias')->insert([
                    'id' => (string) Str::uuid(), 'actor_accesos_id' => $actor['id'], 'actor_nombre_snapshot' => $actor['nombre'],
                    'accion' => 'SOLICITUD_APROBADA', 'entidad' => 'SOLICITUD', 'entidad_id' => $solicitudBloqueada->id,
                    'datos_anteriores' => json_encode(['solicitud_estado_id' => $estadoAnterior]),
                    'datos_nuevos' => json_encode(['solicitud_estado_id' => $aprobadaId, 'documento_emitido_id' => $documentoEmitido?->id, 'instrucciones' => $instrucciones]),
                    'ip' => $request->ip(), 'created_at' => now(),
                ]);
                $notificacionId = (string) Str::uuid();
                DB::table('notificacion_emails')->insert(['id' => $notificacionId, 'solicitud_id' => $solicitudBloqueada->id, 'notificacion_estado_id' => $pendienteId, 'destinatario' => $solicitudBloqueada->email_contacto, 'tipo' => 'SOLICITUD_APROBADA', 'asunto' => 'Solicitud aprobada · Folio '.$solicitudBloqueada->folio, 'intentos' => 0, 'created_at' => now(), 'updated_at' => now()]);

                return [$notificacionId, $documentoEmitido, $instrucciones];
            });
        } catch (\Throwable $exception) {
            if ($archivoGuardado) {
                Storage::disk('local')->delete($archivoGuardado);
            }
            throw $exception;
        }

        $solicitud->refresh()->load('tramite');
        $this->enviarAprobacion($solicitud, $notificacionId, $documentoEmitido, $instrucciones);

        return redirect()->route('solicitudes.show', $solicitud)->with('success', 'La solicitud fue aprobada correctamente.');
    }

    public function rechazar(Request $request, Solicitud $solicitud)
    {
        $solicitud->load('estado');
        $estadosRechazables = ['ENVIADA', 'CORRECCION_SOLICITADA', 'VALIDADA_PARA_PAGO', 'EN_ESPERA_DE_PAGO', 'EN_REVISION_FINAL'];
        abort_unless(in_array($solicitud->estado?->clave, $estadosRechazables, true), 422, 'La solicitud no puede rechazarse en su estado actual.');

        $datos = $request->validate([
            'motivo' => ['required', 'string', 'max:5000'],
        ]);
        $motivo = trim($datos['motivo']);
        $actor = [
            'id' => $request->session()->get('accesos.id'),
            'nombre' => $request->session()->get('accesos.nombre'),
            'departamento' => $request->session()->get('accesos.departamento'),
        ];

        $notificacionId = DB::transaction(function () use ($solicitud, $motivo, $actor, $request) {
            $solicitudBloqueada = Solicitud::query()->lockForUpdate()->findOrFail($solicitud->id);
            $estadoAnterior = $solicitudBloqueada->solicitud_estado_id;
            $estadoActual = $solicitudBloqueada->estado?->clave;
            $permitidos = ['ENVIADA', 'CORRECCION_SOLICITADA', 'VALIDADA_PARA_PAGO', 'EN_ESPERA_DE_PAGO', 'EN_REVISION_FINAL'];
            abort_unless(in_array($estadoActual, $permitidos, true), 422, 'La solicitud ya fue atendida.');

            $rechazadaId = EstadoSolicitud::query()->where('clave', 'RECHAZADA')->where('activo', true)->value('id');
            $pendienteId = DB::table('notificacion_estados')->where('clave', 'PENDIENTE')->where('activo', true)->value('id');
            abort_unless($rechazadaId && $pendienteId, 500);

            $solicitudBloqueada->update(['solicitud_estado_id' => $rechazadaId, 'fecha_terminacion' => now()]);
            DB::table('solicitud_historial_estados')->insert([
                'id' => (string) Str::uuid(), 'solicitud_id' => $solicitudBloqueada->id,
                'estado_anterior_id' => $estadoAnterior, 'estado_nuevo_id' => $rechazadaId, 'comentario' => $motivo,
                'actor_tipo' => 'SERVIDOR_PUBLICO', 'actor_accesos_id' => $actor['id'],
                'actor_nombre_snapshot' => $actor['nombre'], 'actor_departamento_snapshot' => $actor['departamento'],
                'fecha' => now(),
            ]);
            DB::table('auditorias')->insert([
                'id' => (string) Str::uuid(), 'actor_accesos_id' => $actor['id'],
                'actor_nombre_snapshot' => $actor['nombre'], 'accion' => 'SOLICITUD_RECHAZADA',
                'entidad' => 'SOLICITUD', 'entidad_id' => $solicitudBloqueada->id,
                'datos_anteriores' => json_encode(['solicitud_estado_id' => $estadoAnterior]),
                'datos_nuevos' => json_encode(['solicitud_estado_id' => $rechazadaId, 'motivo' => $motivo]),
                'ip' => $request->ip(), 'created_at' => now(),
            ]);

            $notificacionId = (string) Str::uuid();
            DB::table('notificacion_emails')->insert([
                'id' => $notificacionId, 'solicitud_id' => $solicitudBloqueada->id,
                'notificacion_estado_id' => $pendienteId, 'destinatario' => $solicitudBloqueada->email_contacto,
                'tipo' => 'SOLICITUD_RECHAZADA', 'asunto' => 'Solicitud rechazada · Folio '.$solicitudBloqueada->folio,
                'intentos' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);

            return $notificacionId;
        });

        $solicitud->refresh()->load('tramite');
        $this->enviarRechazo($solicitud, $notificacionId, $motivo);

        return redirect()->route('solicitudes.show', $solicitud)->with('success', 'La solicitud fue rechazada correctamente.');
    }

    private function origenFichaPago(): string
    {
        if (! Schema::hasTable('configuraciones_generales')) {
            return 'SISTEMA_EXTERNO';
        }

        return ConfiguracionGeneral::query()->value('origen_ficha_pago') ?? 'SISTEMA_EXTERNO';
    }

    private function validarFichaPago(Request $request): array
    {
        return $request->validate([
            'ficha' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'indicaciones' => ['required', 'string', 'max:5000'],
        ]);
    }

    private function autorizarFichaPago(Solicitud $solicitud): void
    {
        $permitido = ($solicitud->flujo_pago === 'PAGO_DIRECTO' && $solicitud->estado?->clave === 'ENVIADA')
            || ($solicitud->flujo_pago === 'PAGO_POST_VALIDACION' && $solicitud->estado?->clave === 'VALIDADA_PARA_PAGO');

        abort_unless($permitido && $solicitud->importe_pago !== null && $solicitud->pago_vigencia_dias, 422, 'La ficha de pago no está disponible para esta solicitud.');
    }

    private function autorizarReemplazoFichaPago(Solicitud $solicitud): void
    {
        $pago = $solicitud->pago;
        abort_unless($solicitud->estado?->clave === 'EN_ESPERA_DE_PAGO' && $pago && ! $pago->fecha_pago_validado && ! $pago->vencido(), 422, 'La ficha de pago no puede reemplazarse.');
    }

    private function guardarFichaPago($archivo, string $indicaciones, SolicitudPago $pago, int $version, ?string $motivoReemplazo, array $actor, ?string &$archivoGuardado): SolicitudPagoFicha
    {
        $storageKey = $archivo->store('solicitudes/'.$pago->solicitud_id.'/pagos/fichas', 'local');
        $archivoGuardado = $storageKey;

        return SolicitudPagoFicha::query()->create([
            'id' => (string) Str::uuid(),
            'solicitud_pago_id' => $pago->id,
            'version' => $version,
            'nombre_original' => $archivo->getClientOriginalName(),
            'storage_key' => $storageKey,
            'mime_type' => $archivo->getMimeType(),
            'tamano_bytes' => $archivo->getSize(),
            'hash_archivo' => hash_file('sha256', $archivo->getRealPath()),
            'indicaciones' => trim($indicaciones),
            'motivo_reemplazo' => $motivoReemplazo,
            'es_vigente' => true,
            'actor_accesos_id' => $actor['id'],
            'actor_nombre_snapshot' => $actor['nombre'],
            'actor_departamento_snapshot' => $actor['departamento'],
        ]);
    }

    private function autorizarReferenciaPago(Solicitud $solicitud): void
    {
        $permitido = ($solicitud->flujo_pago === 'PAGO_DIRECTO' && $solicitud->estado?->clave === 'ENVIADA') || ($solicitud->flujo_pago === 'PAGO_POST_VALIDACION' && $solicitud->estado?->clave === 'VALIDADA_PARA_PAGO');

        abort_unless($permitido && $solicitud->importe_pago !== null && $solicitud->pago_vigencia_dias, 422, 'La referencia de pago no esta disponible para esta solicitud.');
    }

    private function crearVistaPreviaReferenciaPago(Request $request, Solicitud $solicitud): array
    {
        $vistaPrevia = $this->vistaPreviaReferenciaPago($request, $solicitud);

        if ($vistaPrevia) {
            return $vistaPrevia;
        }

        do {
            $referencia = 'NEZA-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (SolicitudPago::query()->where('referencia', $referencia)->exists());

        $fechaEmision = now();
        $vistaPrevia = [
            'referencia' => $referencia,
            'fecha_emision' => $fechaEmision->toIso8601String(),
            'fecha_vencimiento' => $fechaEmision->copy()->addDays($solicitud->pago_vigencia_dias)->toIso8601String(),
            'expira_en' => now()->addMinutes(15)->toIso8601String(),
        ];

        $request->session()->put($this->llaveVistaPreviaReferenciaPago($solicitud), $vistaPrevia);

        return $vistaPrevia;
    }

    private function vistaPreviaReferenciaPago(Request $request, Solicitud $solicitud): ?array
    {
        $vistaPrevia = $request->session()->get($this->llaveVistaPreviaReferenciaPago($solicitud));

        if (! is_array($vistaPrevia) || ! isset($vistaPrevia['referencia'], $vistaPrevia['fecha_emision'], $vistaPrevia['fecha_vencimiento'], $vistaPrevia['expira_en']) || Carbon::parse($vistaPrevia['expira_en'])->isPast()) {
            $request->session()->forget($this->llaveVistaPreviaReferenciaPago($solicitud));

            return null;
        }

        return $vistaPrevia;
    }

    private function llaveVistaPreviaReferenciaPago(Solicitud $solicitud): string
    {
        return 'referencia_pago_previa.'.$solicitud->id;
    }

    private function actor(Request $request): array
    {
        $actorId = $request->session()->get('accesos.id');
        $actorNombre = $request->session()->get('accesos.nombre');

        return [
            'id' => filled($actorId) ? $actorId : 'PENDIENTE_INTEGRACION_ACCESOS',
            'nombre' => filled($actorNombre) ? $actorNombre : 'Servidor público pendiente de integración con Accesos',
            'departamento' => $request->session()->get('accesos.departamento'),
        ];
    }

    private function estadoId(string $clave): int
    {
        return EstadoSolicitud::query()->where('clave', $clave)->where('activo', true)->value('id') ?? abort(500, 'No se encontró el estado requerido.');
    }

    private function moverEstado(Request $request, Solicitud $solicitud, string $nuevoClave, string $accion, string $comentario): void
    {
        $actor = $this->actor($request);
        DB::transaction(function () use ($request, $solicitud, $nuevoClave, $accion, $comentario, $actor) {
            $bloqueada = Solicitud::query()->with('estado')->lockForUpdate()->findOrFail($solicitud->id);
            abort_unless($bloqueada->flujo_pago === 'PAGO_POST_VALIDACION' && $bloqueada->estado?->clave === 'ENVIADA', 422, 'La solicitud cambió de estado.');
            $anterior = $bloqueada->solicitud_estado_id;
            $nuevo = $this->estadoId($nuevoClave);
            $bloqueada->update(['solicitud_estado_id' => $nuevo]);
            $this->registrarMovimiento($bloqueada, $anterior, $nuevo, $comentario, $actor, $accion, $request);
        });
    }

    private function registrarMovimiento(Solicitud $solicitud, int $anterior, int $nuevo, string $comentario, array $actor, string $accion, Request $request): void
    {
        DB::table('solicitud_historial_estados')->insert(['id' => (string) Str::uuid(), 'solicitud_id' => $solicitud->id, 'estado_anterior_id' => $anterior, 'estado_nuevo_id' => $nuevo, 'comentario' => $comentario, 'actor_tipo' => 'SERVIDOR_PUBLICO', 'actor_accesos_id' => $actor['id'], 'actor_nombre_snapshot' => $actor['nombre'], 'actor_departamento_snapshot' => $actor['departamento'], 'fecha' => now()]);
        DB::table('auditorias')->insert(['id' => (string) Str::uuid(), 'actor_accesos_id' => $actor['id'], 'actor_nombre_snapshot' => $actor['nombre'], 'accion' => $accion, 'entidad' => 'SOLICITUD', 'entidad_id' => $solicitud->id, 'datos_anteriores' => json_encode(['solicitud_estado_id' => $anterior]), 'datos_nuevos' => json_encode(['solicitud_estado_id' => $nuevo, 'comentario' => $comentario]), 'ip' => $request->ip(), 'created_at' => now()]);
    }

    private function notificacionPendiente(Solicitud $solicitud, string $tipo, string $asunto): string
    {
        $id = (string) Str::uuid();
        DB::table('notificacion_emails')->insert(['id' => $id, 'solicitud_id' => $solicitud->id, 'notificacion_estado_id' => DB::table('notificacion_estados')->where('clave', 'PENDIENTE')->where('activo', true)->value('id'), 'destinatario' => $solicitud->email_contacto, 'tipo' => $tipo, 'asunto' => $asunto, 'intentos' => 0, 'created_at' => now(), 'updated_at' => now()]);

        return $id;
    }

    private function enviarReferenciaPago(Solicitud $solicitud, SolicitudPago $pago, string $notificacionId): void
    {
        $this->enviarCorreo($solicitud, $notificacionId, new SolicitudReferenciaPagoMail($solicitud, $pago), 'referencia de pago');
    }

    private function enviarFichaPagoPorCorreo(Solicitud $solicitud, SolicitudPagoFicha $ficha, string $notificacionId, bool $esReemplazo): void
    {
        $this->enviarCorreo($solicitud, $notificacionId, new SolicitudReferenciaPagoMail($solicitud, $ficha, $esReemplazo), 'ficha de pago');
    }

    private function enviarComentario(Solicitud $solicitud, string $comentario, string $notificacionId): void
    {
        $this->enviarCorreo($solicitud, $notificacionId, new SolicitudComentarioMail($solicitud, $comentario), 'comentario de solicitud');
    }

    private function enviarCorreo(Solicitud $solicitud, string $notificacionId, object $correo, string $contexto): void
    {
        try {
            Mail::to($solicitud->email_contacto)->send($correo);
            DB::table('notificacion_emails')->where('id', $notificacionId)->update(['notificacion_estado_id' => DB::table('notificacion_estados')->where('clave', 'ENVIADO')->value('id'), 'intentos' => 1, 'fecha_envio' => now(), 'updated_at' => now()]);
        } catch (\Throwable $exception) {
            DB::table('notificacion_emails')->where('id', $notificacionId)->update(['notificacion_estado_id' => DB::table('notificacion_estados')->where('clave', 'ERROR')->value('id'), 'intentos' => 1, 'error' => Str::limit($exception->getMessage(), 65535, ''), 'updated_at' => now()]);
            Log::error('No se pudo enviar '.$contexto.'.', ['solicitud_id' => $solicitud->id, 'exception' => $exception]);
        }
    }

    private function enviarAprobacion(Solicitud $solicitud, string $notificacionId, ?SolicitudDocumentoEmitido $documentoEmitido, ?string $instrucciones): void
    {
        try {
            Mail::to($solicitud->email_contacto)->send(new SolicitudAprobadaMail($solicitud, $documentoEmitido, $instrucciones));
            DB::table('notificacion_emails')->where('id', $notificacionId)->update(['notificacion_estado_id' => DB::table('notificacion_estados')->where('clave', 'ENVIADO')->value('id'), 'intentos' => 1, 'fecha_envio' => now(), 'updated_at' => now()]);
        } catch (\Throwable $exception) {
            DB::table('notificacion_emails')->where('id', $notificacionId)->update(['notificacion_estado_id' => DB::table('notificacion_estados')->where('clave', 'ERROR')->value('id'), 'intentos' => 1, 'error' => Str::limit($exception->getMessage(), 65535, ''), 'updated_at' => now()]);
            Log::error('No se pudo enviar la aprobación de solicitud.', ['solicitud_id' => $solicitud->id, 'exception' => $exception]);
        }
    }

    private function enviarRechazo(Solicitud $solicitud, string $notificacionId, string $motivo): void
    {
        try {
            Mail::to($solicitud->email_contacto)->send(new SolicitudRechazadaMail($solicitud, $motivo));
            DB::table('notificacion_emails')->where('id', $notificacionId)->update([
                'notificacion_estado_id' => DB::table('notificacion_estados')->where('clave', 'ENVIADO')->value('id'),
                'intentos' => 1, 'fecha_envio' => now(), 'updated_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            DB::table('notificacion_emails')->where('id', $notificacionId)->update([
                'notificacion_estado_id' => DB::table('notificacion_estados')->where('clave', 'ERROR')->value('id'),
                'intentos' => 1, 'error' => Str::limit($exception->getMessage(), 65535, ''), 'updated_at' => now(),
            ]);
            Log::error('No se pudo enviar el rechazo de solicitud.', ['solicitud_id' => $solicitud->id, 'exception' => $exception]);
        }
    }

    private function enviarSolicitudCorreccion(Solicitud $solicitud, string $notificacionId, array $documentos, string $url, string $password): void
    {
        try {
            Mail::to($solicitud->email_contacto)->send(new SolicitudCorreccionDocumentosMail($solicitud, $documentos, $url, $password));
            DB::table('notificacion_emails')->where('id', $notificacionId)->update([
                'notificacion_estado_id' => DB::table('notificacion_estados')->where('clave', 'ENVIADO')->value('id'),
                'intentos' => 1, 'fecha_envio' => now(), 'updated_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            DB::table('notificacion_emails')->where('id', $notificacionId)->update([
                'notificacion_estado_id' => DB::table('notificacion_estados')->where('clave', 'ERROR')->value('id'),
                'intentos' => 1, 'error' => Str::limit($exception->getMessage(), 65535, ''), 'updated_at' => now(),
            ]);
            Log::error('No se pudo enviar la solicitud de corrección de documentos.', ['solicitud_id' => $solicitud->id, 'exception' => $exception]);
        }
    }

    public function descargarDocumento(Solicitud $solicitud, string $documento): StreamedResponse
    {
        $registro = DB::table('solicitud_documentos')
            ->where('id', $documento)
            ->where('solicitud_id', $solicitud->id)
            ->firstOrFail();

        abort_unless(Storage::disk('local')->exists($registro->storage_key), 404);

        return Storage::disk('local')->download($registro->storage_key, $registro->nombre_original);
    }

    public function previsualizarDocumento(Solicitud $solicitud, string $documento): StreamedResponse
    {
        $registro = DB::table('solicitud_documentos')
            ->where('id', $documento)
            ->where('solicitud_id', $solicitud->id)
            ->firstOrFail();

        abort_unless(Storage::disk('local')->exists($registro->storage_key), 404);

        return Storage::disk('local')->response($registro->storage_key, $registro->nombre_original, [
            'Content-Disposition' => 'inline; filename="'.$registro->nombre_original.'"',
        ]);
    }

    public function descargarArchivoRespuesta(Solicitud $solicitud, string $campo): StreamedResponse
    {
        $registro = DB::table('solicitud_respuestas')
            ->where('solicitud_id', $solicitud->id)
            ->where('campo_id', $campo)
            ->value('valor');
        $metadata = json_decode($registro ?: '', true) ?: [];

        abort_unless(isset($metadata['storage_key'], $metadata['nombre_original']) && Storage::disk('local')->exists($metadata['storage_key']), 404);

        return Storage::disk('local')->download($metadata['storage_key'], $metadata['nombre_original']);
    }

    public function previsualizarArchivoRespuesta(Solicitud $solicitud, string $campo): StreamedResponse
    {
        $registro = DB::table('solicitud_respuestas')
            ->where('solicitud_id', $solicitud->id)
            ->where('campo_id', $campo)
            ->value('valor');
        $metadata = json_decode($registro ?: '', true) ?: [];

        abort_unless(isset($metadata['storage_key'], $metadata['nombre_original']) && Storage::disk('local')->exists($metadata['storage_key']), 404);

        return Storage::disk('local')->response($metadata['storage_key'], $metadata['nombre_original'], [
            'Content-Disposition' => 'inline; filename="'.$metadata['nombre_original'].'"',
        ]);
    }

    private function formatearValor(mixed $valor, ?string $tipoCampo = null, $opciones = null): string
    {
        if ($tipoCampo === 'ESTADO' && is_string($valor)) {
            return config('formulario.entidades_federativas.'.$valor, $valor);
        }

        if (in_array($tipoCampo, ['SELECT', 'RADIO', 'MULTISELECT'], true) && $opciones) {
            $etiquetas = collect($opciones)->mapWithKeys(
                fn ($opcion) => [(string) $opcion->valor => $opcion->etiqueta]
            );

            if (is_array($valor)) {
                return collect($valor)
                    ->filter(fn ($elemento) => filled($elemento))
                    ->map(fn ($elemento) => $etiquetas->get((string) $elemento, (string) $elemento))
                    ->implode(', ');
            }

            return (string) $etiquetas->get((string) $valor, (string) $valor);
        }

        if ($tipoCampo === 'CHECKBOX') {
            return filled($valor) ? 'Sí' : 'No';
        }

        if (is_array($valor)) {
            return collect($valor)
                ->filter(fn ($elemento) => filled($elemento))
                ->map(fn ($elemento) => is_scalar($elemento) ? (string) $elemento : json_encode($elemento, JSON_UNESCAPED_UNICODE))
                ->implode(', ');
        }

        return filled($valor) ? (string) $valor : 'Sin respuesta';
    }
}
