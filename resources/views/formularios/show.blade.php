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

        @if ($version->estado->clave !== 'PUBLICADO')
            <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Esta es una vista previa de la versión {{ $version->numero_version }} en estado {{ $version->estado->nombre }}. Aún no está disponible para ciudadanos.</div>
        @endif

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
                                @elseif (in_array($campo->tipo, ['SELECT', 'MULTISELECT']))
                                    <select data-campo-control="{{ $campo->id }}" @if ($campo->tipo === 'MULTISELECT') multiple @endif class="mt-2 block w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm text-neza-ink focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"><option value="">Selecciona una opción</option>@foreach ($campo->opciones->where('activo', true)->sortBy('orden') as $opcion)<option value="{{ $opcion->valor }}" data-valor-condicional="{{ $opcion->etiqueta }}">{{ $opcion->etiqueta }}</option>@endforeach</select>
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
