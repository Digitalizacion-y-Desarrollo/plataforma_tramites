@extends('layouts.publico')

@section('title', 'Iniciar '.$tramite->nombre)

@php($secciones = $formularioVersion->secciones->sortBy('orden'))
@php($totalSecciones = $secciones->count())

@section('content')
    <section class="bg-neza-canvas px-4 py-8 sm:px-6 lg:px-[72px]">
        <div class="mx-auto max-w-3xl">
            <a href="{{ route('portal.tramites.detalle', $tramite) }}" class="inline-flex items-center gap-2 text-sm font-bold text-neza-wine">← Volver al trámite</a>
            <div class="mt-5 rounded-2xl border border-neza-line bg-white p-6 shadow-sm sm:p-8">
                <p class="text-xs font-bold uppercase tracking-wide text-neza-wine">{{ $tramite->nombre }}</p>
                <h1 class="mt-2 text-3xl font-bold">Completa tu solicitud</h1>
                <p class="mt-2 text-sm text-neza-muted">Llena la información requerida para iniciar el trámite.</p>

                @if (session('success'))<div class="mt-5 rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>@endif
                @if ($errors->any())<div id="resumen-errores" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"><p class="font-bold">No se pudo guardar la solicitud.</p><ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><p class="mt-2">Si seleccionaste archivos, vuelve a adjuntarlos antes de enviar.</p></div>@endif

                @if ($totalSecciones > 0)
                    <nav aria-label="Secciones del formulario" class="mt-6 rounded-xl border border-neza-line bg-neza-canvas p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-neza-muted">Formulario de {{ $totalSecciones }} {{ $totalSecciones === 1 ? 'sección' : 'secciones' }}</p>
                        <ol class="mt-3 {{ $totalSecciones === 1 ? 'hidden' : 'flex flex-wrap gap-x-5 gap-y-2' }}">
                            <li class="flex items-center gap-2 text-sm">
                                <span class="grid size-6 shrink-0 place-items-center rounded-full bg-neza-wine text-[11px] font-bold text-white" aria-hidden="true">1</span>
                                <span class="font-semibold text-neza-ink">Datos para seguimiento</span>
                            </li>
                            @foreach ($secciones as $seccion)
                                <li class="flex items-center gap-2 text-sm">
                                    <span class="grid size-6 shrink-0 place-items-center rounded-full bg-neza-wine text-[11px] font-bold text-white" aria-hidden="true">{{ $loop->iteration + 1 }}</span>
                                    <span class="font-semibold text-neza-ink">{{ $seccion->titulo }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                @endif

                <form action="{{ route('portal.tramites.solicitudes.store', $tramite) }}" method="POST" enctype="multipart/form-data" class="mt-8 space-y-8" aria-label="Formulario de {{ $tramite->nombre }}" data-formulario-preview>
                    @csrf
                    <input type="hidden" name="formulario_version_id" value="{{ $formularioVersion->id }}">
                    <fieldset id="seccion-0" data-formulario-seccion class="space-y-5 border-b border-neza-line pb-6">
                        <legend class="flex flex-wrap items-center gap-3 text-lg font-bold">Datos para seguimiento <span class="rounded-full bg-neza-wine-soft px-3 py-1 text-xs font-bold text-neza-wine">Sección 1 de {{ $totalSecciones + 1 }}</span></legend>
                        <div class="grid gap-5 sm:grid-cols-2"><div class="sm:col-span-2"><label for="nombres_solicitante" class="block text-sm font-semibold">Nombre(s) <span class="text-rose-700">*</span></label><input id="nombres_solicitante" name="nombres_solicitante" value="{{ old('nombres_solicitante') }}" maxlength="100" autocomplete="given-name" required class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error('nombres_solicitante') border-rose-600 @enderror">@error('nombres_solicitante')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div><div><label for="apellido_paterno_solicitante" class="block text-sm font-semibold">Apellido paterno <span class="text-rose-700">*</span></label><input id="apellido_paterno_solicitante" name="apellido_paterno_solicitante" value="{{ old('apellido_paterno_solicitante') }}" maxlength="100" autocomplete="family-name" required class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error('apellido_paterno_solicitante') border-rose-600 @enderror">@error('apellido_paterno_solicitante')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div><div><label for="apellido_materno_solicitante" class="block text-sm font-semibold">Apellido materno <span class="text-rose-700">*</span></label><input id="apellido_materno_solicitante" name="apellido_materno_solicitante" value="{{ old('apellido_materno_solicitante') }}" maxlength="100" required class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error('apellido_materno_solicitante') border-rose-600 @enderror">@error('apellido_materno_solicitante')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div></div>
                        <div><label for="curp_consulta" class="block text-sm font-semibold">CURP <span class="text-rose-700">*</span></label><input id="curp_consulta" name="curp_consulta" value="{{ old('curp_consulta') }}" maxlength="18" required @error('curp_consulta') aria-invalid="true" aria-describedby="curp_consulta-error" @enderror class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm uppercase @error('curp_consulta') border-rose-600 @enderror">@error('curp_consulta')<p id="curp_consulta-error" class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <div><label for="email_contacto" class="block text-sm font-semibold">Correo electrónico <span class="text-rose-700">*</span></label><input id="email_contacto" type="email" name="email_contacto" value="{{ old('email_contacto') }}" required @error('email_contacto') aria-invalid="true" aria-describedby="email_contacto-error" @enderror class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error('email_contacto') border-rose-600 @enderror">@error('email_contacto')<p id="email_contacto-error" class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <div><label for="telefono_contacto" class="block text-sm font-semibold">Número telefónico <span class="text-rose-700">*</span></label><input id="telefono_contacto" type="tel" name="telefono_contacto" value="{{ old('telefono_contacto') }}" maxlength="30" autocomplete="tel" required class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error('telefono_contacto') border-rose-600 @enderror">@error('telefono_contacto')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    </fieldset>
                    @forelse ($secciones as $seccion)
                        <fieldset id="seccion-{{ $loop->iteration }}" data-formulario-seccion class="space-y-5 border-t border-neza-line pt-6 first:border-0 first:pt-0">
                            <legend class="flex flex-wrap items-center gap-3 text-lg font-bold">{{ $seccion->titulo }} <span class="rounded-full bg-neza-wine-soft px-3 py-1 text-xs font-bold text-neza-wine">Sección {{ $loop->iteration + 1 }} de {{ $totalSecciones + 1 }}</span></legend>
                            @if ($seccion->descripcion)<p class="text-sm text-neza-muted">{{ $seccion->descripcion }}</p>@endif
                            @foreach ($seccion->campos->sortBy('orden') as $campo)
                                @php($condiciones = $campo->condicionesDestino->map(fn ($condicion) => ['origen' => $condicion->campo_origen_id, 'operador' => $condicion->operador, 'valor' => data_get($condicion->valor_comparacion, 'valor'), 'accion' => $condicion->accion]))
                                @php($condicionesGrupo = $campo->gruposCampos->flatMap->condicionesDestino->map(fn ($condicion) => ['origen' => $condicion->campo_origen_id, 'operador' => $condicion->operador, 'valor' => data_get($condicion->valor_comparacion, 'valor'), 'accion' => $condicion->accion]))
                                <div data-campo-destino="{{ $campo->id }}" data-requerido-inicial="{{ $campo->requerido ? 'true' : 'false' }}" data-condiciones='@json(array_values(array_merge($condiciones->all(), $condicionesGrupo->all())))'>
                                    <label for="campo-{{ $campo->id }}" class="block text-sm font-semibold text-neza-ink">{{ $campo->etiqueta }} @if($campo->requerido)<span class="text-rose-700">*</span>@endif</label>
                                    @if (in_array($campo->tipo, ['TEXTAREA'], true))
                                        <textarea id="campo-{{ $campo->id }}" data-campo-control="{{ $campo->id }}" name="respuestas[{{ $campo->id }}]" rows="4" @required($campo->requerido) @error('respuestas.'.$campo->id) aria-invalid="true" @enderror class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error('respuestas.'.$campo->id) border-rose-600 @enderror">{{ old('respuestas.'.$campo->id) }}</textarea>
                                    @elseif ($campo->tipo === 'DOMICILIO')
                                        <div class="mt-2" data-domicilio data-indice-url="{{ asset('data/capa.json') }}">
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            @foreach (config('formulario.campos_domicilio') as $clave => $subcampo)
                                                <div @class(['sm:col-span-2' => $clave === 'calle'])>
                                                    <label for="campo-{{ $campo->id }}-{{ $clave }}" class="block text-xs font-semibold text-neza-muted">{{ $subcampo['etiqueta'] }}</label>
                                                    <input id="campo-{{ $campo->id }}-{{ $clave }}" data-campo-control="{{ $campo->id }}" data-domicilio-campo="{{ $clave }}" name="respuestas[{{ $campo->id }}][{{ $clave }}]" type="{{ $subcampo['tipo'] }}" autocomplete="{{ $subcampo['autocomplete'] }}" value="{{ old('respuestas.'.$campo->id.'.'.$clave, $subcampo['valor'] ?? '') }}" @if (isset($subcampo['inputmode'])) inputmode="{{ $subcampo['inputmode'] }}" @endif @if (isset($subcampo['pattern'])) pattern="{{ $subcampo['pattern'] }}" @endif @if (in_array($clave, ['colonia', 'calle', 'numero'], true)) list="campo-{{ $campo->id }}-{{ $clave }}-opciones" @endif @readonly(($subcampo['disabled'] ?? false) || $clave === 'codigo_postal') @required($campo->requerido && ! ($subcampo['disabled'] ?? false)) class="mt-1 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm read-only:bg-neza-canvas read-only:text-neza-muted">
                                                </div>
                                            @endforeach
                                        </div>
                                        <datalist id="campo-{{ $campo->id }}-colonia-opciones" data-domicilio-colonias></datalist><datalist id="campo-{{ $campo->id }}-calle-opciones" data-domicilio-calles></datalist><datalist id="campo-{{ $campo->id }}-numero-opciones" data-domicilio-numeros></datalist>
                                        <input type="hidden" name="respuestas[{{ $campo->id }}][latitud]" data-domicilio-latitud><input type="hidden" name="respuestas[{{ $campo->id }}][longitud]" data-domicilio-longitud><p class="mt-2 text-xs text-neza-muted">Primero selecciona una colonia; el código postal y las calles disponibles se actualizarán automáticamente.</p>
                                        </div>
                                    @elseif ($campo->tipo === 'DOMICILIO_FUERA_NEZA')
                                        <div class="mt-2 grid gap-3 sm:grid-cols-2" @error('respuestas.'.$campo->id) aria-invalid="true" @enderror>
                                            @foreach (config('formulario.campos_domicilio_fuera_neza') as $clave => $subcampo)
                                                <div @class(['sm:col-span-2' => in_array($clave, ['municipio', 'calle'], true)])>
                                                    <label for="campo-{{ $campo->id }}-{{ $clave }}" class="block text-xs font-semibold text-neza-muted">{{ $subcampo['etiqueta'] }}</label>
                                                    <input id="campo-{{ $campo->id }}-{{ $clave }}" data-campo-control="{{ $campo->id }}" name="respuestas[{{ $campo->id }}][{{ $clave }}]" type="{{ $subcampo['tipo'] }}" maxlength="{{ $subcampo['maxlength'] }}" autocomplete="{{ $subcampo['autocomplete'] }}" value="{{ old('respuestas.'.$campo->id.'.'.$clave) }}" @if (isset($subcampo['inputmode'])) inputmode="{{ $subcampo['inputmode'] }}" @endif @if (isset($subcampo['pattern'])) pattern="{{ $subcampo['pattern'] }}" @endif @required($campo->requerido) @error('respuestas.'.$campo->id) aria-invalid="true" @enderror class="mt-1 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error('respuestas.'.$campo->id) border-rose-600 @enderror">
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif ($campo->tipo === 'DOMICILIO_USA')
                                        <div class="mt-2 grid gap-3 sm:grid-cols-2" @error('respuestas.'.$campo->id) aria-invalid="true" @enderror>
                                            @foreach (config('formulario.campos_domicilio_usa') as $clave => $subcampo)
                                                @php($requeridoSubcampo = $campo->requerido && ($subcampo['requerido'] ?? true))
                                                <div @class(['sm:col-span-2' => $clave === 'calle'])>
                                                    <label for="campo-{{ $campo->id }}-{{ $clave }}" class="block text-xs font-semibold text-neza-muted">{{ $subcampo['etiqueta'] }}</label>
                                                    @if ($clave === 'estado')
                                                        <select id="campo-{{ $campo->id }}-{{ $clave }}" data-campo-control="{{ $campo->id }}" name="respuestas[{{ $campo->id }}][{{ $clave }}]" @required($requeridoSubcampo) @error('respuestas.'.$campo->id) aria-invalid="true" @enderror class="mt-1 w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm @error('respuestas.'.$campo->id) border-rose-600 @enderror">
                                                            <option value="">Selecciona un estado</option>
                                                            @foreach (config('formulario.estados_usa') as $codigo => $nombre)
                                                                <option value="{{ $codigo }}" @selected(old('respuestas.'.$campo->id.'.estado') === $codigo)>{{ $nombre }}</option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        <input id="campo-{{ $campo->id }}-{{ $clave }}" data-campo-control="{{ $campo->id }}" name="respuestas[{{ $campo->id }}][{{ $clave }}]" type="{{ $subcampo['tipo'] }}" maxlength="{{ $subcampo['maxlength'] }}" autocomplete="{{ $subcampo['autocomplete'] }}" value="{{ old('respuestas.'.$campo->id.'.'.$clave) }}" @if (isset($subcampo['inputmode'])) inputmode="{{ $subcampo['inputmode'] }}" @endif @if (isset($subcampo['pattern'])) pattern="{{ $subcampo['pattern'] }}" @endif @required($requeridoSubcampo) @error('respuestas.'.$campo->id) aria-invalid="true" @enderror class="mt-1 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error('respuestas.'.$campo->id) border-rose-600 @enderror">
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                        <p class="mt-2 text-xs text-neza-muted">País: Estados Unidos. Captura la dirección, ciudad, estado y código postal (ZIP).</p>
                                    @elseif ($campo->tipo === 'ESTADO')
                                        <select id="campo-{{ $campo->id }}" data-campo-control="{{ $campo->id }}" name="respuestas[{{ $campo->id }}]" @required($campo->requerido) @error('respuestas.'.$campo->id) aria-invalid="true" @enderror class="mt-2 w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm @error('respuestas.'.$campo->id) border-rose-600 @enderror"><option value="">Selecciona una entidad</option>@foreach (config('formulario.entidades_federativas') as $clave => $entidad)<option value="{{ $clave }}" data-valor-condicional="{{ $entidad }}" @selected(old('respuestas.'.$campo->id) === $clave)>{{ $entidad }}</option>@endforeach</select>
                                    @elseif (in_array($campo->tipo, ['SELECT', 'MULTISELECT'], true))
                                        <select id="campo-{{ $campo->id }}" data-campo-control="{{ $campo->id }}" name="respuestas[{{ $campo->id }}]{{ $campo->tipo === 'MULTISELECT' ? '[]' : '' }}" @if($campo->tipo === 'MULTISELECT') multiple @endif @required($campo->requerido) @error('respuestas.'.$campo->id) aria-invalid="true" @enderror class="mt-2 w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm @error('respuestas.'.$campo->id) border-rose-600 @enderror"><option value="">Selecciona una opción</option>@foreach($campo->opciones->where('activo', true)->sortBy('orden') as $opcion)<option value="{{ $opcion->valor }}" data-valor-condicional="{{ $opcion->etiqueta }}" @selected(in_array($opcion->valor, (array) old('respuestas.'.$campo->id, []), true))>{{ $opcion->etiqueta }}</option>@endforeach</select>
                                    @elseif ($campo->tipo === 'RADIO')
                                        <div class="mt-2 space-y-2" @error('respuestas.'.$campo->id) aria-invalid="true" @enderror>@foreach($campo->opciones->where('activo', true)->sortBy('orden') as $opcion)<label class="flex items-center gap-2 text-sm text-neza-ink"><input data-campo-control="{{ $campo->id }}" type="radio" name="respuestas[{{ $campo->id }}]" value="{{ $opcion->valor }}" data-valor-condicional="{{ $opcion->etiqueta }}" @checked(old('respuestas.'.$campo->id) == $opcion->valor) @required($campo->requerido) class="size-4 border-neza-line text-neza-wine focus:ring-neza-wine">{{ $opcion->etiqueta }}</label>@endforeach</div>
                                    @elseif ($campo->tipo === 'CHECKBOX')
                                        <label class="mt-2 flex items-center gap-2 text-sm text-neza-ink" @error('respuestas.'.$campo->id) aria-invalid="true" @enderror><input id="campo-{{ $campo->id }}" data-campo-control="{{ $campo->id }}" type="checkbox" name="respuestas[{{ $campo->id }}]" value="1" @checked(old('respuestas.'.$campo->id)) @required($campo->requerido) class="size-4 rounded border-neza-line text-neza-wine focus:ring-neza-wine">Seleccionar</label>
                                    @elseif ($campo->tipo === 'FILE')
                                        <input id="campo-{{ $campo->id }}" data-campo-control="{{ $campo->id }}" data-file-input data-max-bytes="10485760" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" name="respuestas[{{ $campo->id }}]" type="file" @required($campo->requerido) @error('respuestas.'.$campo->id) aria-invalid="true" @enderror class="mt-2 block w-full rounded-lg border border-neza-line bg-white px-3 py-2 text-sm text-neza-muted file:mr-4 file:rounded-md file:border-0 file:bg-neza-wine-soft file:px-3 file:py-2 file:text-xs file:font-bold file:text-neza-wine @error('respuestas.'.$campo->id) border-rose-600 @enderror">
                                        <p class="mt-2 text-xs text-neza-muted">PDF, imágenes o documentos permitidos. Tamaño máximo: 10 MB.</p>
                                    @else
                                        <input id="campo-{{ $campo->id }}" data-campo-control="{{ $campo->id }}" name="respuestas[{{ $campo->id }}]" value="{{ old('respuestas.'.$campo->id) }}" type="{{ $campo->tipo === 'EMAIL' ? 'email' : ($campo->tipo === 'DATE' ? 'date' : ($campo->tipo === 'NUMBER' ? 'number' : 'text')) }}" @if($campo->tipo === 'CURP') pattern="[A-Za-z]{4}[0-9]{6}[A-Za-z]{6}[A-Za-z0-9][0-9]" @endif @required($campo->requerido) @error('respuestas.'.$campo->id) aria-invalid="true" @enderror class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error('respuestas.'.$campo->id) border-rose-600 @enderror">
                                    @endif
                                    @error('respuestas.'.$campo->id)<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                        </fieldset>
                    @empty
                        <p class="rounded-xl border border-dashed border-neza-line p-5 text-sm text-neza-muted">El formulario todavía no contiene campos configurados.</p>
                    @endforelse
                    @if ($errors->has('formulario_version_id'))
                        <p class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $errors->first('formulario_version_id') }}</p>
                    @endif
                    <fieldset data-formulario-privacidad class="rounded-xl border border-neza-line bg-neza-canvas p-5">
                        <legend class="px-2 text-sm font-bold text-neza-ink">Aviso de privacidad</legend>
                        <label class="flex items-start gap-3 text-sm leading-relaxed text-neza-muted">
                            <input type="checkbox" name="acepto_aviso_privacidad" value="1" required @checked(old('acepto_aviso_privacidad')) class="mt-1 size-4 shrink-0 rounded border-neza-line text-neza-wine focus:ring-neza-wine">
                            <span>Acepto el <a href="{{ route('portal.aviso-privacidad') }}" target="_blank" rel="noopener" class="font-semibold text-neza-wine underline underline-offset-2">aviso de privacidad</a> y el tratamiento de mis datos personales para atender este trámite.</span>
                        </label>
                        @error('acepto_aviso_privacidad')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </fieldset>
                    <div class="flex flex-wrap gap-3">
                        <button type="button" data-seccion-anterior hidden class="h-12 rounded-xl border border-neza-line px-6 text-sm font-bold text-neza-wine focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Anterior</button>
                        <button type="button" data-seccion-siguiente hidden class="h-12 rounded-xl bg-neza-wine px-6 text-sm font-bold text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Siguiente</button>
                        <button type="submit" data-seccion-enviar class="h-12 rounded-xl bg-neza-wine px-6 text-sm font-bold text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Enviar solicitud</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[aria-label^="Formulario"]');
    if (!form) return;
    ['nombres_solicitante', 'apellido_paterno_solicitante', 'apellido_materno_solicitante'].forEach((name) => { form.elements[name].minLength = 2; });
    form.elements.curp_consulta.pattern = '[A-Za-z]{4}[0-9]{6}[A-Za-z]{6}[A-Za-z0-9][0-9]';
    form.elements.telefono_contacto.pattern = '[0-9+()\\-\\s]{7,30}';
    form.querySelectorAll('[data-campo-destino] input, [data-campo-destino] select, [data-campo-destino] textarea').forEach((input) => {
        input.dataset.requeridoOriginal = input.required ? 'true' : 'false';
    });
    const valueOf = (id) => {
        const controls = [...form.querySelectorAll('[data-campo-control="' + id + '"]')];
        const source = controls[0] ?? document.getElementById('campo-' + id);
        if (!source) return '';
        if (source.type === 'radio') return controls.find((control) => control.checked)?.dataset.valorCondicional ?? '';
        if (source.tagName === 'SELECT') return [...source.selectedOptions].map((option) => option.dataset.valorCondicional ?? option.text).join(', ');
        return source.type === 'checkbox' ? (source.checked ? 'true' : '') : source.value;
    };
    const evaluate = (value, condition) => { const actual = String(value ?? '').trim().toLocaleLowerCase(); const expected = String(condition.valor ?? '').trim().toLocaleLowerCase(); return ({ EQUALS: actual === expected, NOT_EQUALS: actual !== expected, CONTAINS: actual.includes(expected), GREATER_THAN: Number(actual) > Number(expected), LESS_THAN: Number(actual) < Number(expected), IS_EMPTY: actual === '', IS_NOT_EMPTY: actual !== '' }[condition.operador] ?? false); };
    const refresh = () => form.querySelectorAll('[data-campo-destino]').forEach(function (container) {
        const conditions = JSON.parse(container.dataset.condiciones);
        const matches = (action) => conditions.filter((condition) => condition.accion === action).map((condition) => evaluate(valueOf(condition.origen), condition));
        const show = matches('SHOW'); const hide = matches('HIDE'); const require = matches('REQUIRE'); const optional = matches('OPTIONAL');
        const visible = (show.length === 0 || show.every(Boolean)) && !hide.some(Boolean);
        let required = container.dataset.requeridoInicial === 'true';
        if (require.length) required = require.every(Boolean);
        if (optional.some(Boolean)) required = false;
        container.hidden = !visible;
        container.querySelectorAll('input, select, textarea').forEach((input) => {
            const requeridoOriginal = input.dataset.requeridoOriginal === 'true';
            input.required = visible && required && (container.dataset.requeridoInicial !== 'true' || requeridoOriginal);
            input.disabled = !visible;
        });
    });
    form.addEventListener('change', refresh); form.addEventListener('input', refresh); refresh();
    const secciones = [...form.querySelectorAll('[data-formulario-seccion]')];
    const privacidad = form.querySelector('[data-formulario-privacidad]');
    const anterior = form.querySelector('[data-seccion-anterior]');
    const siguiente = form.querySelector('[data-seccion-siguiente]');
    const enviar = form.querySelector('[data-seccion-enviar]');
    const primeraSeccionConError = secciones.findIndex((seccion) => seccion.querySelector('[aria-invalid="true"], p.text-rose-700'));
    let actual = primeraSeccionConError >= 0 ? primeraSeccionConError : (privacidad.querySelector('p.text-rose-700') ? secciones.length - 1 : 0);
    const mostrar = (indice, enfocar = true) => {
        actual = indice;
        secciones.forEach((seccion, i) => { seccion.hidden = i !== actual; });
        privacidad.hidden = actual !== secciones.length - 1;
        anterior.hidden = actual === 0;
        siguiente.hidden = actual === secciones.length - 1;
        enviar.hidden = actual !== secciones.length - 1;
        if (enfocar) {
            const titulo = secciones[actual].querySelector('legend');
            titulo.setAttribute('tabindex', '-1');
            titulo.focus();
            titulo.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };
    const mostrarError = (input) => {
        let mensaje = input.parentElement.querySelector('[data-error-validacion]');
        if (!mensaje) {
            mensaje = document.createElement('p');
            mensaje.dataset.errorValidacion = '';
            mensaje.className = 'mt-2 text-sm text-rose-700';
            mensaje.setAttribute('role', 'alert');
            input.insertAdjacentElement('afterend', mensaje);
        }
        mensaje.textContent = input.validationMessage;
        input.setAttribute('aria-invalid', 'true');
    };
    form.addEventListener('input', (event) => {
        const input = event.target;
        if (!input.matches('input, select, textarea')) return;
        const mensaje = input.parentElement.querySelector('[data-error-validacion]');
        if (mensaje && input.validity.valid) { mensaje.remove(); input.removeAttribute('aria-invalid'); }
    });
    siguiente.addEventListener('click', () => {
        const invalido = [...secciones[actual].querySelectorAll('input, select, textarea')].find((input) => input.willValidate && !input.checkValidity());
        if (invalido) { mostrarError(invalido); invalido.focus(); return; }
        mostrar(actual + 1);
    });
    anterior.addEventListener('click', () => mostrar(actual - 1));
    form.addEventListener('invalid', (event) => {
        const indice = secciones.findIndex((seccion) => seccion.contains(event.target));
        if (indice >= 0 && indice !== actual) mostrar(indice, false);
        mostrarError(event.target);
    }, true);
    mostrar(actual, false);
    const primerError = secciones[actual].querySelector('[aria-invalid="true"]');
    if (primerError && typeof primerError.focus === 'function') { primerError.focus(); primerError.scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
    const resumen = document.getElementById('resumen-errores');
    if (resumen) { resumen.setAttribute('tabindex', '-1'); resumen.focus(); }
});
</script>
@endsection
