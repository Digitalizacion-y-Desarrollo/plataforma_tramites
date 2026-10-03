@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <a href="{{ route('formulario.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-neza-muted hover:text-neza-wine">
                    <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    Volver al listado
                </a>
                <p class="mt-5 text-sm font-semibold text-neza-wine">Vista previa ciudadana</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-neza-ink">{{ $formulario->nombre }}</h1>
                @if ($formulario->descripcion)<p class="mt-2 text-sm text-neza-muted">{{ $formulario->descripcion }}</p>@endif
            </div>
            <a href="{{ route('formulario.edit', $formulario) }}" class="inline-flex w-fit rounded-lg border border-neza-line px-4 py-2.5 text-sm font-semibold text-neza-muted hover:bg-neza-canvas">Editar formulario</a>
        </div>

        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Esta es una vista previa administrativa de la versión {{ $version->numero_version }}. La disponibilidad ciudadana depende del estado del trámite.</div>

        <section class="overflow-hidden rounded-xl border border-neza-line bg-white shadow-sm">
            <div class="border-b border-neza-line px-6 py-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-neza-muted">Trámite</p>
                <p class="mt-1 font-semibold text-neza-ink">{{ $version->tramites->where('pivot.activo', true)->pluck('nombre')->join(', ') ?: 'No asignado' }}</p>
                <p class="mt-2 text-sm text-neza-muted">Departamento responsable: {{ $formulario->departamento_nombre_snapshot }}@if ($formulario->subdepartamento_nombre_snapshot) · {{ $formulario->subdepartamento_nombre_snapshot }}@endif</p>
            </div>
            <div class="space-y-8 p-6" role="form" aria-label="Vista previa del formulario" data-formulario-preview>
                @foreach ($version->gruposCampos as $grupo)
                    @foreach ($grupo->condicionesDestino as $condicion)
                        <span hidden data-grupo-condicion data-grupo-destino="{{ $grupo->id }}" data-origen="{{ $condicion->campo_origen_id }}" data-operador="{{ $condicion->operador }}" data-valor="{{ data_get($condicion->valor_comparacion, 'valor') }}" data-accion="{{ $condicion->accion }}"></span>
                    @endforeach
                @endforeach
                @forelse ($version->secciones->sortBy('orden') as $seccion)
                    <fieldset class="space-y-5">
                        <legend class="text-lg font-bold text-neza-ink">{{ $seccion->titulo }}</legend>
                        @if ($seccion->descripcion)<p class="-mt-3 text-sm text-neza-muted">{{ $seccion->descripcion }}</p>@endif
                        @forelse ($seccion->campos->sortBy('orden') as $campo)
                            <div data-campo-destino="{{ $campo->id }}" data-grupos-destino="{{ $campo->gruposCampos->pluck('id')->join(',') }}" data-requerido-inicial="{{ $campo->requerido ? 'true' : 'false' }}">
                                @foreach ($campo->condicionesDestino as $condicion)
                                    <span hidden data-condicion data-origen="{{ $condicion->campo_origen_id }}" data-operador="{{ $condicion->operador }}" data-valor="{{ data_get($condicion->valor_comparacion, 'valor') }}" data-accion="{{ $condicion->accion }}"></span>
                                @endforeach
                                <label class="block text-sm font-semibold text-neza-ink">{{ $campo->etiqueta }}@if ($campo->requerido)<span class="text-rose-700"> *</span>@endif</label>
                                @if ($campo->tipo === 'TEXTAREA')
                                    <textarea data-campo-control="{{ $campo->id }}" rows="4" placeholder="Respuesta del ciudadano" class="mt-2 block w-full resize-none rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm placeholder:text-neza-muted focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"></textarea>
                                @elseif ($campo->tipo === 'DOMICILIO')
                                    <div class="mt-2" data-domicilio data-indice-url="{{ asset('data/domicilios-index.json') }}">
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            @foreach (config('formulario.campos_domicilio') as $clave => $subcampo)
                                                <div class="{{ $clave === 'calle' ? 'sm:col-span-2' : '' }}"><label class="block text-xs font-semibold text-neza-muted" for="domicilio-{{ $campo->id }}-{{ $clave }}">{{ $subcampo['etiqueta'] }}</label><input id="domicilio-{{ $campo->id }}-{{ $clave }}" name="campo[{{ $campo->id }}][{{ $clave }}]" type="{{ $subcampo['tipo'] }}" autocomplete="{{ $subcampo['autocomplete'] }}" value="{{ $subcampo['valor'] ?? '' }}" @if (in_array($clave, ['colonia', 'calle', 'numero'])) list="domicilio-{{ $campo->id }}-{{ $clave }}-opciones" @endif data-domicilio-campo="{{ $clave }}" @disabled($subcampo['disabled'] ?? false) @required($campo->requerido && ! ($subcampo['disabled'] ?? false)) class="mt-1 block w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm text-neza-ink placeholder:text-neza-muted focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft disabled:cursor-not-allowed disabled:bg-neza-canvas disabled:text-neza-muted"></div>
                                            @endforeach
                                        </div>
                                        <datalist id="domicilio-{{ $campo->id }}-colonia-opciones" data-domicilio-colonias></datalist>
                                        <datalist id="domicilio-{{ $campo->id }}-calle-opciones" data-domicilio-calles></datalist>
                                        <datalist id="domicilio-{{ $campo->id }}-numero-opciones" data-domicilio-numeros></datalist>
                                        <input type="hidden" name="campo[{{ $campo->id }}][latitud]" data-domicilio-latitud>
                                        <input type="hidden" name="campo[{{ $campo->id }}][longitud]" data-domicilio-longitud>
                                        <p class="mt-2 text-xs text-neza-muted" data-domicilio-estado aria-live="polite">Ingresa un código postal de cinco dígitos para consultar colonias.</p>
                                    </div>
                                @elseif (in_array($campo->tipo, ['SELECT', 'MULTISELECT', 'ESTADO']))
                                    <select data-campo-control="{{ $campo->id }}" @if ($campo->tipo === 'MULTISELECT') multiple @endif class="mt-2 block w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm text-neza-ink focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"><option value="">Selecciona una opción</option>@if ($campo->tipo === 'ESTADO')@foreach (config('formulario.entidades_federativas') as $clave => $entidad)<option value="{{ $clave }}" data-valor-condicional="{{ $entidad }}">{{ $entidad }}</option>@endforeach @else @foreach ($campo->opciones->where('activo', true)->sortBy('orden') as $opcion)<option value="{{ $opcion->valor }}" data-valor-condicional="{{ $opcion->etiqueta }}">{{ $opcion->etiqueta }}</option>@endforeach @endif</select>
                                @elseif ($campo->tipo === 'RADIO')
                                    <div class="mt-2 space-y-2">@forelse ($campo->opciones->where('activo', true)->sortBy('orden') as $opcion)<label class="flex items-center gap-2 text-sm text-neza-ink"><input data-campo-control="{{ $campo->id }}" data-valor-condicional="{{ $opcion->etiqueta }}" type="radio" name="campo-{{ $campo->id }}" value="{{ $opcion->valor }}" class="size-4 border-neza-line text-neza-wine focus:ring-neza-wine"> {{ $opcion->etiqueta }}</label>@empty<p class="text-sm text-neza-muted">Sin opciones configuradas.</p>@endforelse</div>
                                @elseif ($campo->tipo === 'CHECKBOX')
                                    <label class="mt-2 flex items-center gap-2 text-sm text-neza-muted"><input data-campo-control="{{ $campo->id }}" type="checkbox" class="size-4 rounded border-neza-line text-neza-wine focus:ring-neza-wine"> Selección del ciudadano</label>
                                @elseif ($campo->tipo === 'FILE')
                                    <input data-campo-control="{{ $campo->id }}" type="file" class="mt-2 block w-full rounded-lg border border-neza-line bg-white px-3 py-2 text-sm text-neza-muted focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft">
                                @else
                                    <input data-campo-control="{{ $campo->id }}" type="{{ $campo->tipo === 'EMAIL' ? 'email' : ($campo->tipo === 'DATE' ? 'date' : ($campo->tipo === 'NUMBER' ? 'number' : 'text')) }}" placeholder="Respuesta del ciudadano" class="mt-2 block w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm placeholder:text-neza-muted focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft">
                                @endif
                                <p class="mt-1 text-xs text-neza-muted">{{ $campo->tipo }}</p>
                            </div>
                        @empty
                            <p class="rounded-lg border border-dashed border-neza-line px-4 py-5 text-sm text-neza-muted">Esta sección todavía no tiene campos configurados.</p>
                        @endforelse
                    </fieldset>
                @empty
                    <div class="py-12 text-center"><h2 class="font-semibold text-neza-ink">Este formulario no tiene secciones todavía</h2><p class="mt-2 text-sm text-neza-muted">Agrega secciones y campos desde el editor para visualizarlo aquí.</p></div>
                @endforelse
                @if ($version->secciones->isNotEmpty())
                    <button type="button" class="w-full rounded-lg bg-neza-wine px-4 py-3 text-sm font-semibold text-white">Enviar solicitud</button>
                @endif
            </div>
        </section>
    </div>
@endsection
