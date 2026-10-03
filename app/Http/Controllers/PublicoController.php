<?php

namespace App\Http\Controllers;

use App\Models\Tramite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicoController extends Controller
{
    public function index(): View
    {
        $tramites = $this->tramitesPublicados();

        return view('publico.index', compact('tramites'));
    }

    public function tramites(): View
    {
        $tramites = $this->tramitesPublicados();

        return view('publico.tramites.index', compact('tramites'));
    }

    public function predial(): View
    {
        if (! Schema::hasTable('tramites')) {
            return view('publico.tramites.predial');
        }

        $tramite = Tramite::query()->where('activo', true)->where('clave', 'PREDIAL')->first();
        $formularioVersion = $tramite ? $this->formularioActivo($tramite) : null;

        return view('publico.tramites.detalle', compact('tramite', 'formularioVersion'));
    }

    public function detalle(Tramite $tramite): View
    {
        abort_unless($tramite->activo, 404);

        $formularioVersion = $this->formularioActivo($tramite);

        return view('publico.tramites.detalle', compact('tramite', 'formularioVersion'));
    }

    public function iniciar(Tramite $tramite): View
    {
        abort_unless($tramite->activo, 404);

        $formularioVersion = $this->formularioActivo($tramite);
        abort_unless($formularioVersion, 404);

        return view('publico.tramites.formulario', compact('tramite', 'formularioVersion'));
    }

    public function iniciarPredial()
    {
        if (! Schema::hasTable('tramites')) {
            return view('publico.tramites.predial-iniciar');
        }

        $tramite = Tramite::query()
            ->where('clave', 'PREDIAL')
            ->where('activo', true)
            ->firstOrFail();

        return redirect()->route('portal.tramites.iniciar', $tramite);
    }

    public function storeSolicitud(Request $request, Tramite $tramite)
    {
        abort_unless($tramite->activo, 404);
        $formularioVersion = $this->formularioActivo($tramite);
        abort_unless($formularioVersion, 404);
        $base = $request->validate([
            'curp_consulta' => ['required', 'string', 'regex:/^[A-Z]{4}[0-9]{6}[A-Z]{6}[A-Z0-9][0-9]$/i'],
            'email_contacto' => ['required', 'email', 'max:200'],
        ]);

        $campos = $formularioVersion->secciones->where('activo', true)->flatMap(fn ($seccion) => $seccion->campos->where('activo', true))->keyBy('id');
        $respuestas = $request->input('respuestas', []);
        foreach ($campos as $campo) {
            if (! array_key_exists($campo->id, $respuestas) && $request->has($campo->clave)) {
                $respuestas[$campo->id] = $request->input($campo->clave);
            }
        }
        $errores = [];
        foreach ($campos as $campo) {
            $requerido = $this->esVisible($campo, $respuestas) && $this->esRequerido($campo, $respuestas);
            $reglas = [$requerido ? 'required' : 'nullable'];
            $valor = $campo->tipo === 'FILE' ? $request->file('respuestas.'.$campo->id) : ($respuestas[$campo->id] ?? null);
            if ($campo->tipo === 'FILE') $reglas = [$requerido ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'];
            if ($campo->tipo === 'ESTADO') $reglas[] = 'in:'.implode(',', array_keys(config('formulario.entidades_federativas')));
            if ($campo->tipo === 'DOMICILIO') $reglas[] = 'array';
            if ($campo->tipo === 'EMAIL') $reglas[] = 'email:rfc,dns';
            if ($campo->tipo === 'NUMBER') $reglas[] = 'numeric';
            if ($campo->tipo === 'DATE') $reglas[] = 'date';
            if ($campo->tipo === 'CURP') $reglas[] = 'regex:/^[A-Z]{4}[0-9]{6}[A-Z]{6}[A-Z0-9][0-9]$/i';
            $reglasValor = ['valor' => $reglas];
            if ($campo->tipo === 'MULTISELECT') {
                $reglasValor['valor'] = [$requerido ? 'required' : 'nullable', 'array'];
                $reglasValor['valor.*'] = ['in:'.$campo->opciones->where('activo', true)->pluck('valor')->implode(',')];
            } elseif (in_array($campo->tipo, ['SELECT', 'RADIO'], true)) {
                $reglasValor['valor'][] = 'in:'.$campo->opciones->where('activo', true)->pluck('valor')->implode(',');
            }
            $validacion = validator(['valor' => $valor], $reglasValor);
            if ($validacion->fails()) $errores['respuestas.'.$campo->id] = $validacion->errors()->first('valor');
        }
        if ($errores) return back()->withErrors($errores)->withInput();

        $folio = DB::transaction(function () use ($tramite, $formularioVersion, $campos, $respuestas, $base) {
            $estadoId = DB::table('solicitud_estados')->where('clave', 'ENVIADA')->where('activo', true)->value('id');
            abort_unless($estadoId, 500);
            $id = (string) Str::uuid();
            do { $folio = 'SOL-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)); } while (DB::table('solicitudes')->where('folio', $folio)->exists());
            DB::table('solicitudes')->insert(['id' => $id, 'folio' => $folio, 'tramite_id' => $tramite->id, 'formulario_version_id' => $formularioVersion->id, 'solicitud_estado_id' => $estadoId, 'curp_consulta' => Str::upper($base['curp_consulta']), 'email_contacto' => $base['email_contacto'], 'fecha_envio' => now(), 'created_at' => now(), 'updated_at' => now()]);
            foreach ($campos as $campo) {
                $valor = $respuestas[$campo->id] ?? null;
                if ($campo->tipo === 'FILE' && request()->hasFile('respuestas.'.$campo->id)) {
                    $archivo = request()->file('respuestas.'.$campo->id);
                    $valor = ['nombre_original' => $archivo->getClientOriginalName(), 'storage_key' => $archivo->store('solicitudes/'.$id, 'local'), 'mime_type' => $archivo->getMimeType(), 'tamano_bytes' => $archivo->getSize()];
                }
                if (filled($valor)) DB::table('solicitud_respuestas')->insert(['id' => (string) Str::uuid(), 'solicitud_id' => $id, 'campo_id' => $campo->id, 'valor' => json_encode($valor), 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('solicitud_historial_estados')->insert(['id' => (string) Str::uuid(), 'solicitud_id' => $id, 'estado_nuevo_id' => $estadoId, 'comentario' => 'Solicitud enviada por la ciudadanía.', 'actor_tipo' => 'CIUDADANO', 'fecha' => now()]);
            return $folio;
        });
        return redirect()->route('portal.tramites.iniciar', $tramite)->with('success', 'Tu solicitud fue enviada. Folio: '.$folio);
    }

    private function formularioActivo(Tramite $tramite)
    {
        return $tramite->formulariosVersiones()
            ->wherePivot('activo', true)
            ->whereHas('formulario')
            ->with(['formulario', 'secciones.campos.opciones', 'secciones.campos.gruposCampos.condicionesDestino.campoOrigen.opciones', 'secciones.campos.condicionesDestino.campoOrigen.opciones'])
            ->orderByDesc('numero_version')
            ->first();
    }

    private function tramitesPublicados()
    {
        if (! Schema::hasTable('tramite_estados')) {
            return collect();
        }

        return Tramite::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
    }

    private function esRequerido($campo, array $respuestas): bool
    {
        $requerido = $campo->requerido;
        foreach ($this->condiciones($campo) as $condicion) {
            $valor = $respuestas[$condicion->campo_origen_id] ?? null;
            $coincide = $this->coincideCondicion($condicion, $valor);
            if ($coincide && $condicion->accion === 'REQUIRE') $requerido = true;
            if ($coincide && $condicion->accion === 'OPTIONAL') $requerido = false;
        }
        return $requerido;
    }

    private function esVisible($campo, array $respuestas): bool
    {
        $mostrar = [];
        $ocultar = [];
        foreach ($this->condiciones($campo) as $condicion) {
            $coincide = $this->coincideCondicion($condicion, $respuestas[$condicion->campo_origen_id] ?? null);
            if ($condicion->accion === 'SHOW') $mostrar[] = $coincide;
            if ($condicion->accion === 'HIDE') $ocultar[] = $coincide;
        }
        return ($mostrar === [] || ! in_array(false, $mostrar, true)) && ! in_array(true, $ocultar, true);
    }

    private function coincideCondicion($condicion, mixed $valor): bool
    {
        $comparacion = data_get($condicion->valor_comparacion, 'valor');
        $valorVisible = $condicion->campoOrigen?->opciones->firstWhere('valor', $valor)?->etiqueta ?? $valor;
        return match ($condicion->operador) {
            'EQUALS' => (string) $valorVisible === (string) $comparacion,
            'NOT_EQUALS' => (string) $valorVisible !== (string) $comparacion,
            'CONTAINS' => str_contains((string) $valorVisible, (string) $comparacion),
            'GREATER_THAN' => (float) $valorVisible > (float) $comparacion,
            'LESS_THAN' => (float) $valorVisible < (float) $comparacion,
            'IS_EMPTY' => blank($valorVisible), 'IS_NOT_EMPTY' => filled($valorVisible), default => false,
        };
    }

    private function condiciones($campo): array
    {
        return array_merge(
            $campo->condicionesDestino->all(),
            $campo->gruposCampos->flatMap(fn ($grupo) => $grupo->condicionesDestino)->all(),
        );
    }
}
