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
                @if ($errors->any())<div id="resumen-errores" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"><p class="font-bold">No se pudo guardar la solicitud.</p><ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

                @if ($totalSecciones > 0)
                    <nav aria-label="Secciones del formulario" class="mt-6 rounded-xl border border-neza-line bg-neza-canvas p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-neza-muted">Formulario de {{ $totalSecciones }} {{ $totalSecciones === 1 ? 'sección' : 'secciones' }}</p>
                        <ol class="mt-3 {{ $totalSecciones === 1 ? 'hidden' : 'flex flex-wrap gap-x-5 gap-y-2' }}">
                            <li class="flex items-center gap-2 text-sm">
                                <span class="grid size-6 shrink-0 place-items-center rounded-full bg-neza-wine text-[11px] font-bold text-white" aria-hidden="true">1</span>
                                <a href="#seccion-0" class="font-semibold text-neza-ink underline-offset-2 hover:text-neza-wine hover:underline">Datos para seguimiento</a>
                            </li>
                            @foreach ($secciones as $seccion)
                                <li class="flex items-center gap-2 text-sm">
                                    <span class="grid size-6 shrink-0 place-items-center rounded-full bg-neza-wine text-[11px] font-bold text-white" aria-hidden="true">{{ $loop->iteration + 1 }}</span>
                                    <a href="#seccion-{{ $loop->iteration }}" class="font-semibold text-neza-ink underline-offset-2 hover:text-neza-wine hover:underline">{{ $seccion->titulo }}</a>
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                @endif

                <form action="{{ route('portal.tramites.solicitudes.store', $tramite) }}" method="POST" enctype="multipart/form-data" class="mt-8 space-y-8" aria-label="Formulario de {{ $tramite->nombre }}" data-formulario-preview data-confirm-datos>
                    @csrf
                    <fieldset id="seccion-0" class="space-y-5 border-b border-neza-line pb-6">
                        <legend class="flex flex-wrap items-center gap-3 text-lg font-bold">Datos para seguimiento <span class="rounded-full bg-neza-wine-soft px-3 py-1 text-xs font-bold text-neza-wine">Sección 1 de {{ $totalSecciones + 1 }}</span></legend>
                        <div><label for="nombre_solicitante" class="block text-sm font-semibold">Nombre completo <span class="text-rose-700">*</span></label><input id="nombre_solicitante" name="nombre_solicitante" value="{{ old('nombre_solicitante') }}" maxlength="200" autocomplete="name" required @error('nombre_solicitante') aria-invalid="true" aria-describedby="nombre_solicitante-error" @enderror class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm focus-visible:outline-2 focus-visible:outline-neza-wine @error('nombre_solicitante') border-rose-600 @enderror">@error('nombre_solicitante')<p id="nombre_solicitante-error" class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <div><label for="curp_consulta" class="block text-sm font-semibold">CURP <span class="text-rose-700">*</span></label><input id="curp_consulta" name="curp_consulta" value="{{ old('curp_consulta') }}" maxlength="18" required @error('curp_consulta') aria-invalid="true" aria-describedby="curp_consulta-error" @enderror class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm uppercase @error('curp_consulta') border-rose-600 @enderror">@error('curp_consulta')<p id="curp_consulta-error" class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <div><label for="email_contacto" class="block text-sm font-semibold">Correo electrónico <span class="text-rose-700">*</span></label><input id="email_contacto" type="email" name="email_contacto" value="{{ old('email_contacto') }}" required @error('email_contacto') aria-invalid="true" aria-describedby="email_contacto-error" @enderror class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error('email_contacto') border-rose-600 @enderror">@error('email_contacto')<p id="email_contacto-error" class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    </fieldset>
                    @forelse ($secciones as $seccion)
                        <fieldset id="seccion-{{ $loop->iteration }}" class="space-y-5 border-t border-neza-line pt-6 first:border-0 first:pt-0">
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
                                        <div class="mt-2" data-domicilio data-indice-url="{{ asset('data/domicilios-index.json') }}">
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            @foreach (config('formulario.campos_domicilio') as $clave => $subcampo)
                                                <div @class(['sm:col-span-2' => $clave === 'calle'])>
                                                    <label for="campo-{{ $campo->id }}-{{ $clave }}" class="block text-xs font-semibold text-neza-muted">{{ $subcampo['etiqueta'] }}</label>
                                                    <input id="campo-{{ $campo->id }}-{{ $clave }}" data-campo-control="{{ $campo->id }}" data-domicilio-campo="{{ $clave }}" name="respuestas[{{ $campo->id }}][{{ $clave }}]" type="{{ $subcampo['tipo'] }}" autocomplete="{{ $subcampo['autocomplete'] }}" value="{{ old('respuestas.'.$campo->id.'.'.$clave, $subcampo['valor'] ?? '') }}" @if (in_array($clave, ['colonia', 'calle', 'numero'], true)) list="campo-{{ $campo->id }}-{{ $clave }}-opciones" @endif @readonly($subcampo['disabled'] ?? false) @required($campo->requerido && ! ($subcampo['disabled'] ?? false)) class="mt-1 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm read-only:bg-neza-canvas read-only:text-neza-muted">
                                                </div>
                                            @endforeach
                                        </div>
                                        <datalist id="campo-{{ $campo->id }}-colonia-opciones" data-domicilio-colonias></datalist><datalist id="campo-{{ $campo->id }}-calle-opciones" data-domicilio-calles></datalist><datalist id="campo-{{ $campo->id }}-numero-opciones" data-domicilio-numeros></datalist>
                                        <input type="hidden" name="respuestas[{{ $campo->id }}][latitud]" data-domicilio-latitud><input type="hidden" name="respuestas[{{ $campo->id }}][longitud]" data-domicilio-longitud><p class="mt-2 text-xs text-neza-muted" data-domicilio-estado aria-live="polite">Ingresa un código postal de cinco dígitos para consultar opciones.</p>
                                        </div>
                                    @elseif ($campo->tipo === 'ESTADO')
                                        <select id="campo-{{ $campo->id }}" data-campo-control="{{ $campo->id }}" name="respuestas[{{ $campo->id }}]" @required($campo->requerido) @error('respuestas.'.$campo->id) aria-invalid="true" @enderror class="mt-2 w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm @error('respuestas.'.$campo->id) border-rose-600 @enderror"><option value="">Selecciona una entidad</option>@foreach (config('formulario.entidades_federativas') as $clave => $entidad)<option value="{{ $clave }}" data-valor-condicional="{{ $entidad }}" @selected(old('respuestas.'.$campo->id) === $clave)>{{ $entidad }}</option>@endforeach</select>
                                    @elseif (in_array($campo->tipo, ['SELECT', 'MULTISELECT'], true))
                                        <select id="campo-{{ $campo->id }}" data-campo-control="{{ $campo->id }}" name="respuestas[{{ $campo->id }}]{{ $campo->tipo === 'MULTISELECT' ? '[]' : '' }}" @if($campo->tipo === 'MULTISELECT') multiple @endif @required($campo->requerido) @error('respuestas.'.$campo->id) aria-invalid="true" @enderror class="mt-2 w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm @error('respuestas.'.$campo->id) border-rose-600 @enderror"><option value="">Selecciona una opción</option>@foreach($campo->opciones->where('activo', true)->sortBy('orden') as $opcion)<option value="{{ $opcion->valor }}" data-valor-condicional="{{ $opcion->etiqueta }}" @selected(in_array($opcion->valor, (array) old('respuestas.'.$campo->id, []), true))>{{ $opcion->etiqueta }}</option>@endforeach</select>
                                    @elseif ($campo->tipo === 'RADIO')
                                        <div class="mt-2 space-y-2" @error('respuestas.'.$campo->id) aria-invalid="true" @enderror>@foreach($campo->opciones->where('activo', true)->sortBy('orden') as $opcion)<label class="flex items-center gap-2 text-sm text-neza-ink"><input data-campo-control="{{ $campo->id }}" type="radio" name="respuestas[{{ $campo->id }}]" value="{{ $opcion->valor }}" data-valor-condicional="{{ $opcion->etiqueta }}" @checked(old('respuestas.'.$campo->id) == $opcion->valor) @required($campo->requerido) class="size-4 border-neza-line text-neza-wine focus:ring-neza-wine">{{ $opcion->etiqueta }}</label>@endforeach</div>
                                    @elseif ($campo->tipo === 'CHECKBOX')
                                        <label class="mt-2 flex items-center gap-2 text-sm text-neza-ink" @error('respuestas.'.$campo->id) aria-invalid="true" @enderror><input id="campo-{{ $campo->id }}" data-campo-control="{{ $campo->id }}" type="checkbox" name="respuestas[{{ $campo->id }}]" value="1" @checked(old('respuestas.'.$campo->id)) @required($campo->requerido) class="size-4 rounded border-neza-line text-neza-wine focus:ring-neza-wine">Seleccionar</label>
                                    @elseif ($campo->tipo === 'FILE')
                                        <input id="campo-{{ $campo->id }}" data-campo-control="{{ $campo->id }}" data-file-input name="respuestas[{{ $campo->id }}]" type="file" @required($campo->requerido) @error('respuestas.'.$campo->id) aria-invalid="true" @enderror class="mt-2 block w-full rounded-lg border border-neza-line bg-white px-3 py-2 text-sm text-neza-muted file:mr-4 file:rounded-md file:border-0 file:bg-neza-wine-soft file:px-3 file:py-2 file:text-xs file:font-bold file:text-neza-wine @error('respuestas.'.$campo->id) border-rose-600 @enderror">
                                        <p class="mt-2 text-xs text-neza-muted">PDF, imágenes o documentos permitidos. Tamaño máximo: 10 MB.</p>
                                    @else
                                        <input id="campo-{{ $campo->id }}" data-campo-control="{{ $campo->id }}" name="respuestas[{{ $campo->id }}]" value="{{ old('respuestas.'.$campo->id) }}" type="{{ $campo->tipo === 'EMAIL' ? 'email' : ($campo->tipo === 'DATE' ? 'date' : ($campo->tipo === 'NUMBER' ? 'number' : 'text')) }}" @required($campo->requerido) @error('respuestas.'.$campo->id) aria-invalid="true" @enderror class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error('respuestas.'.$campo->id) border-rose-600 @enderror">
                                    @endif
                                    @error('respuestas.'.$campo->id)<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                        </fieldset>
                    @empty
                        <p class="rounded-xl border border-dashed border-neza-line p-5 text-sm text-neza-muted">El formulario todavía no contiene campos configurados.</p>
                    @endforelse
                    <button type="submit" class="h-12 rounded-xl bg-neza-wine px-6 text-sm font-bold text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Enviar solicitud</button>
                </form>
            </div>
        </div>
    </section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[aria-label^="Formulario"]');
    if (!form) return;
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
        container.querySelectorAll('input, select, textarea').forEach((input) => { input.required = visible && required; input.disabled = !visible; });
    });
    form.addEventListener('change', refresh); form.addEventListener('input', refresh); refresh();
    const primerError = form.querySelector('[aria-invalid="true"]');
    if (primerError) { primerError.focus(); primerError.scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
    const resumen = document.getElementById('resumen-errores');
    if (resumen) { resumen.setAttribute('tabindex', '-1'); resumen.focus(); }
});
</script>
@endsection
