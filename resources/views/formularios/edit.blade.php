@extends('layouts.app')

@section('content')
    @php
        $esBorrador = $version->estado->clave === 'BORRADOR';
    @endphp
    <div class="mx-auto max-w-5xl space-y-5">
        <section class="flex flex-col gap-4 border-b border-neza-line pb-5 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <a href="{{ route('formulario.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-neza-muted hover:text-neza-wine">
                    <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    Volver al listado
                </a>
                <p class="mt-5 text-sm font-semibold text-neza-wine">Versión {{ $version->numero_version }}</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-neza-ink">{{ $formulario->nombre }}</h1>
                @if ($formulario->descripcion)<p class="mt-2 text-sm text-neza-muted">{{ $formulario->descripcion }}</p>@endif
            </div>
        </section>

        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('success') }}</div>
        @endif
        @error('seccion')
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ $message }}</div>
        @enderror

        @if ($esBorrador)
            <details class="rounded-xl border border-neza-line bg-white shadow-sm">
                <summary class="cursor-pointer px-6 py-5"><span class="font-bold text-neza-ink">Información general</span><span class="ml-2 text-sm text-neza-muted">Nombre, trámite y departamento responsable</span></summary>
                <form action="{{ route('formulario.update', $formulario) }}" method="POST" class="border-t border-neza-line p-6">
                    @csrf @method('PATCH')
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="md:col-span-2"><label for="general-nombre" class="block text-sm font-semibold text-neza-ink">Nombre del formulario <span class="text-rose-700">*</span></label><input id="general-nombre" name="nombre" required maxlength="200" value="{{ old('nombre', $formulario->nombre) }}" class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft">@error('nombre')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <div><label for="general-tramite" class="block text-sm font-semibold text-neza-ink">Trámite <span class="text-rose-700">*</span></label><select id="general-tramite" name="tramite_id" required class="mt-2 w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"><option value="">Selecciona un trámite</option>@foreach ($tramites as $tramite)<option value="{{ $tramite->id }}" @selected((string) old('tramite_id', $version->tramites->first()?->id) === (string) $tramite->id)>{{ $tramite->nombre }}</option>@endforeach</select>@error('tramite_id')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <div><label for="departamento_nombre" class="block text-sm font-semibold text-neza-ink">Departamento responsable <span class="text-rose-700">*</span></label><input id="departamento_nombre" name="departamento_nombre" type="text" list="departamentos-accesos" required autocomplete="off" data-departamento-input value="{{ old('departamento_nombre', $formulario->departamento_nombre_snapshot) }}" class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"><input type="hidden" name="departamento_accesos_id" value="{{ old('departamento_accesos_id', $formulario->departamento_accesos_id) }}" data-departamento-id><datalist id="departamentos-accesos">@foreach (collect($departamentos)->whereNull('parent_id') as $departamento)<option value="{{ $departamento['nombre'] }}{{ $departamento['siglas'] ? ' ('.$departamento['siglas'].')' : '' }}" data-departamento-id="{{ $departamento['id'] }}"></option>@endforeach</datalist>@error('departamento_accesos_id')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <div id="subdepartamento-contenedor" class="hidden"><label for="subdepartamento_nombre" class="block text-sm font-semibold text-neza-ink">Subdepartamento</label><input id="subdepartamento_nombre" name="subdepartamento_nombre" type="text" list="subdepartamentos-accesos" autocomplete="off" data-subdepartamento-input value="{{ old('subdepartamento_nombre', $formulario->subdepartamento_nombre_snapshot) }}" class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"><input type="hidden" name="subdepartamento_accesos_id" value="{{ old('subdepartamento_accesos_id', $formulario->subdepartamento_accesos_id) }}" data-subdepartamento-id><datalist id="subdepartamentos-accesos"></datalist><datalist id="subdepartamentos-origen">@foreach (collect($departamentos)->whereNotNull('parent_id') as $subdepartamento)<option value="{{ $subdepartamento['nombre'] }}{{ $subdepartamento['siglas'] ? ' ('.$subdepartamento['siglas'].')' : '' }}" data-subdepartamento-id="{{ $subdepartamento['id'] }}" data-parent-id="{{ $subdepartamento['parent_id'] }}"></option>@endforeach</datalist>@error('subdepartamento_accesos_id')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <div class="md:col-span-2"><label for="general-descripcion" class="block text-sm font-semibold text-neza-ink">Descripción</label><textarea id="general-descripcion" name="descripcion" rows="3" maxlength="5000" class="mt-2 w-full resize-y rounded-lg border border-neza-line px-3 py-2.5 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft">{{ old('descripcion', $formulario->descripcion) }}</textarea>@error('descripcion')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    </div>
                    <div class="mt-5 flex justify-end"><button type="submit" class="rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Guardar información general</button></div>
                </form>
            </details>
        @endif

        @php
            $camposTotales = $version->secciones->sum(fn ($seccion) => $seccion->campos->count());
            $seccionesVacias = $version->secciones->filter(fn ($seccion) => $seccion->campos->isEmpty())->count();
        @endphp
        <nav class="sticky top-3 z-10 rounded-xl border border-neza-line bg-white/95 p-3 shadow-sm backdrop-blur" aria-label="Pasos del constructor">
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <a href="#secciones" class="rounded-lg bg-neza-wine-soft px-3 py-2 font-semibold text-neza-wine">1. Secciones</a>
                <a href="#preguntas" class="rounded-lg px-3 py-2 font-semibold text-neza-muted hover:bg-neza-canvas hover:text-neza-wine">2. Preguntas</a>
                <a href="#reglas" class="rounded-lg px-3 py-2 font-semibold text-neza-muted hover:bg-neza-canvas hover:text-neza-wine">3. Reglas</a>
                <a href="#revision" class="rounded-lg px-3 py-2 font-semibold text-neza-muted hover:bg-neza-canvas hover:text-neza-wine">4. Revisión</a>
                <a href="{{ route('formulario.show', $formulario) }}" target="_blank" rel="noopener" class="ml-auto inline-flex items-center gap-2 rounded-lg border border-neza-wine px-3 py-2 font-semibold text-neza-wine hover:bg-neza-wine-soft">Vista previa</a>
            </div>
        </nav>

        @if (! $esBorrador)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Esta versión no puede modificarse porque ya no está en borrador.</div>
        @else
            <section id="secciones" class="scroll-mt-24 rounded-xl border border-neza-line bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-neza-ink">Agregar sección</h2>
                <p class="mt-1 text-sm text-neza-muted">Organiza los datos que solicitarás al ciudadano en bloques claros.</p>
                <form action="{{ route('formulario.secciones.store', $formulario) }}" method="POST" class="mt-5 grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
                    @csrf
                    <div><label for="titulo" class="block text-sm font-semibold text-neza-ink">Título de la sección <span class="text-rose-700">*</span></label><input id="titulo" name="titulo" required maxlength="200" placeholder="Ej. Datos del solicitante" class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"></div>
                    <div><label for="descripcion" class="block text-sm font-semibold text-neza-ink">Descripción</label><input id="descripcion" name="descripcion" maxlength="5000" placeholder="Instrucción breve para esta sección" class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"></div>
                    <button type="submit" class="rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Agregar sección</button>
                </form>
                @error('titulo')<p class="mt-3 text-sm text-rose-700">{{ $message }}</p>@enderror
            </section>
        @endif

        <section id="preguntas" class="scroll-mt-24 space-y-4" @if ($esBorrador) data-orden-campos data-orden-url="{{ route('formulario.campos.ordenar', $formulario) }}" data-csrf-token="{{ csrf_token() }}" @endif>
            @php
                $camposVersion = $version->secciones->flatMap(fn ($seccion) => $seccion->campos);
                $tiposCampo = config('formulario.tipos_campo');
            @endphp
            <div class="flex items-center justify-between"><h2 class="text-xl font-bold text-neza-ink">Estructura del formulario</h2><span class="text-sm text-neza-muted">{{ $version->secciones->count() }} secciones</span></div>
            @forelse ($version->secciones->sortBy('orden') as $seccion)
                <article class="overflow-hidden rounded-xl border border-neza-line bg-white shadow-sm">
                    <div class="flex flex-col gap-2 border-b border-neza-line px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-neza-muted">Sección {{ $seccion->orden }}</p><h3 class="mt-1 font-bold text-neza-ink">{{ $seccion->titulo }}</h3>@if ($seccion->descripcion)<p class="mt-1 text-sm text-neza-muted">{{ $seccion->descripcion }}</p>@endif</div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-neza-muted">{{ $seccion->campos->count() }} campos</span>
                            @if ($esBorrador)
                                <div class="flex overflow-hidden rounded-lg border border-neza-line" aria-label="Cambiar orden de la sección">
                                    <form action="{{ route('formulario.secciones.mover', [$formulario, $seccion]) }}" method="POST">@csrf<button name="direccion" value="subir" type="submit" class="px-2 py-1 text-xs font-semibold text-neza-muted hover:bg-neza-canvas hover:text-neza-wine">Subir</button></form>
                                    <form action="{{ route('formulario.secciones.mover', [$formulario, $seccion]) }}" method="POST">@csrf<button name="direccion" value="bajar" type="submit" class="border-l border-neza-line px-2 py-1 text-xs font-semibold text-neza-muted hover:bg-neza-canvas hover:text-neza-wine">Bajar</button></form>
                                </div>
                                <form action="{{ route('formulario.secciones.destroy', [$formulario, $seccion]) }}" method="POST">@csrf @method('DELETE')<button type="submit" class="text-xs font-semibold text-rose-700 hover:underline">Eliminar sección</button></form>
                            @endif
                        </div>
                    </div>
                    <div class="space-y-3 bg-neza-canvas p-4" data-campos-lista data-seccion-id="{{ $seccion->id }}">
                        @forelse ($seccion->campos->sortBy('orden') as $campo)
                            <div class="overflow-hidden rounded-xl border border-neza-line bg-white shadow-sm" @if ($esBorrador) draggable="true" data-campo-id="{{ $campo->id }}" @endif>
                            <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-neza-muted">Pregunta {{ $loop->iteration }}</p>
                                    <p class="font-semibold text-neza-ink">{{ $campo->etiqueta }}</p>
                                    <p class="mt-1 text-xs text-neza-muted">
                                        {{ $tiposCampo[$campo->tipo] ?? $campo->tipo }} · {{ $campo->requerido ? 'Obligatorio' : 'Opcional' }}
                                        @if ($campo->opciones->isNotEmpty())
                                            · {{ $campo->opciones->count() }} opciones
                                        @endif
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                     <span class="rounded-md bg-neza-canvas px-2 py-1 text-xs text-neza-muted">{{ $campo->clave }}</span>
                                     @if ($esBorrador)
                                        <div class="flex overflow-hidden rounded-lg border border-neza-line" aria-label="Cambiar orden de la pregunta">
                                            <form action="{{ route('formulario.secciones.campos.mover', [$formulario, $seccion, $campo]) }}" method="POST">@csrf<button name="direccion" value="subir" type="submit" class="px-2 py-1 text-xs font-semibold text-neza-muted hover:bg-neza-canvas hover:text-neza-wine">Subir</button></form>
                                            <form action="{{ route('formulario.secciones.campos.mover', [$formulario, $seccion, $campo]) }}" method="POST">@csrf<button name="direccion" value="bajar" type="submit" class="border-l border-neza-line px-2 py-1 text-xs font-semibold text-neza-muted hover:bg-neza-canvas hover:text-neza-wine">Bajar</button></form>
                                        </div>
                                        <form action="{{ route('formulario.secciones.campos.destroy', [$formulario, $seccion, $campo]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-semibold text-rose-700 hover:underline">Eliminar</button>
                                        </form>
                                    @endif
                                </div>
                             </div>
                             @if ($esBorrador)
                                <details class="border-t border-neza-line px-5 py-3">
                                    <summary class="cursor-pointer text-sm font-semibold text-neza-wine">Editar pregunta</summary>
                                    <form action="{{ route('formulario.secciones.campos.update', [$formulario, $seccion, $campo]) }}" method="POST" class="mt-4 grid gap-3 md:grid-cols-4">
                                        @csrf @method('PATCH')
                                        <div><label class="block text-xs font-semibold text-neza-muted" for="editar-etiqueta-{{ $campo->id }}">Pregunta</label><input id="editar-etiqueta-{{ $campo->id }}" name="etiqueta" required maxlength="200" value="{{ $campo->etiqueta }}" class="mt-1 w-full rounded-lg border border-neza-line px-3 py-2 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"></div>
                                        <div><label class="block text-xs font-semibold text-neza-muted" for="editar-tipo-{{ $campo->id }}">Tipo de respuesta</label><select id="editar-tipo-{{ $campo->id }}" name="tipo" data-tipo-campo class="mt-1 w-full rounded-lg border border-neza-line px-3 py-2 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft">@foreach ($tiposCampo as $valor => $nombre)<option value="{{ $valor }}" @selected($campo->tipo === $valor)>{{ $nombre }}</option>@endforeach</select></div>
                                        <label class="mt-5 flex items-center gap-2 text-sm text-neza-ink"><input type="checkbox" name="requerido" value="1" @checked($campo->requerido) class="size-4 rounded border-neza-line text-neza-wine focus:ring-neza-wine"> Obligatorio</label>
                                        <button type="submit" class="mt-5 rounded-lg bg-neza-wine px-3 py-2 text-sm font-semibold text-white hover:opacity-90">Guardar cambios</button>
                                        <div class="hidden md:col-span-4" data-opciones-builder>
                                            <label class="block text-xs font-semibold text-neza-muted" for="editar-opcion-{{ $campo->id }}">Opciones de respuesta</label>
                                            <div class="mt-1 flex gap-2"><input id="editar-opcion-{{ $campo->id }}" type="text" data-opcion-input placeholder="Escribe una opción y presiona Enter" class="min-w-0 flex-1 rounded-lg border border-neza-line px-3 py-2 text-sm"><button type="button" data-agregar-opcion class="rounded-lg border border-neza-wine px-3 py-2 text-sm font-semibold text-neza-wine hover:bg-neza-wine-soft">Agregar</button></div>
                                            <div class="mt-2 flex flex-wrap gap-2" data-opciones-lista></div>
                                            <input type="hidden" name="opciones" value="{{ $campo->opciones->sortBy('orden')->pluck('etiqueta')->join("\n") }}" data-opciones-valor>
                                        </div>
                                    </form>
                                </details>
                                <details class="border-t border-neza-line bg-neza-canvas px-5 py-3">
                                    <summary class="cursor-pointer text-sm font-semibold text-neza-wine">Agregar condición</summary>
                                    @if ($campo->condicionesDestino->isNotEmpty())
                                        <ul class="mt-3 space-y-2 text-xs text-neza-muted">
                                            @foreach ($campo->condicionesDestino as $condicion)
                                                <li class="flex items-center justify-between gap-3 rounded-lg border border-neza-line bg-white px-3 py-2"><span>{{ $condicion->accion }} si {{ $condicion->campoOrigen->etiqueta }} {{ $condicion->operador }}{{ data_get($condicion->valor_comparacion, 'valor') ? ' '.data_get($condicion->valor_comparacion, 'valor') : '' }}</span><form action="{{ route('formulario.condiciones.destroy', [$formulario, $condicion]) }}" method="POST">@csrf @method('DELETE')<button type="submit" class="shrink-0 font-semibold text-rose-700 hover:underline">Eliminar condición</button></form></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    <form action="{{ route('formulario.secciones.campos.condiciones.store', [$formulario, $seccion, $campo]) }}" method="POST" class="mt-4 grid gap-3 md:grid-cols-4">
                                        @csrf
                                        <div><label for="origen-{{ $campo->id }}" class="block text-xs font-semibold text-neza-muted">Campo origen</label><select id="origen-{{ $campo->id }}" name="campo_origen_id" required class="mt-1 w-full rounded-lg border border-neza-line bg-white px-3 py-2 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"><option value="">Selecciona un campo</option>@foreach ($camposVersion->where('id', '!=', $campo->id) as $origen)<option value="{{ $origen->id }}">{{ $origen->etiqueta }}</option>@endforeach</select>@error('campo_origen_id')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror</div>
                                        <div><label for="operador-{{ $campo->id }}" class="block text-xs font-semibold text-neza-muted">Operador</label><select id="operador-{{ $campo->id }}" name="operador" class="mt-1 w-full rounded-lg border border-neza-line bg-white px-3 py-2 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft">@foreach (['EQUALS' => 'Es igual a', 'NOT_EQUALS' => 'No es igual a', 'CONTAINS' => 'Contiene', 'GREATER_THAN' => 'Mayor que', 'LESS_THAN' => 'Menor que', 'IS_EMPTY' => 'Está vacío', 'IS_NOT_EMPTY' => 'No está vacío'] as $valor => $etiqueta)<option value="{{ $valor }}">{{ $etiqueta }}</option>@endforeach</select></div>
                                        <div><label for="valor-{{ $campo->id }}" class="block text-xs font-semibold text-neza-muted">Valor esperado</label><input id="valor-{{ $campo->id }}" name="valor" maxlength="200" placeholder="Ej. Sí" class="mt-1 w-full rounded-lg border border-neza-line bg-white px-3 py-2 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft">@error('valor')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror</div>
                                        <div><label for="accion-{{ $campo->id }}" class="block text-xs font-semibold text-neza-muted">Acción</label><select id="accion-{{ $campo->id }}" name="accion" class="mt-1 w-full rounded-lg border border-neza-line bg-white px-3 py-2 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"><option value="SHOW">Mostrar</option><option value="HIDE">Ocultar</option><option value="REQUIRE">Hacer obligatorio</option><option value="OPTIONAL">Hacer opcional</option></select><button type="submit" class="mt-3 w-full rounded-lg border border-neza-wine px-3 py-2 text-sm font-semibold text-neza-wine hover:bg-neza-wine-soft">Guardar condición</button></div>
                                    </form>
                                </details>
                            @endif
                            </div>
                        @empty
                            <p data-lista-vacia class="px-5 py-4 text-sm text-neza-muted">Esta sección aún no tiene campos. Arrastra una pregunta aquí.</p>
                        @endforelse
                    </div>
                    @if ($esBorrador)
                        <form action="{{ route('formulario.secciones.campos.store', [$formulario, $seccion]) }}" method="POST" class="grid gap-3 border-t border-neza-line bg-neza-canvas px-5 py-4 md:grid-cols-[minmax(0,1fr)_180px_auto_auto] md:items-end">
                            @csrf
                            <div class="md:col-span-4"><p class="text-xs font-semibold text-neza-muted">Agregar rápidamente</p><div class="mt-2 flex flex-wrap gap-2" data-plantillas-pregunta><button type="button" data-etiqueta="Nombre completo" data-tipo="TEXT" class="rounded-full border border-neza-line bg-white px-3 py-1 text-xs font-semibold text-neza-muted hover:border-neza-wine hover:text-neza-wine">Nombre completo</button><button type="button" data-etiqueta="Correo electrónico" data-tipo="EMAIL" class="rounded-full border border-neza-line bg-white px-3 py-1 text-xs font-semibold text-neza-muted hover:border-neza-wine hover:text-neza-wine">Correo</button><button type="button" data-etiqueta="Teléfono" data-tipo="PHONE" class="rounded-full border border-neza-line bg-white px-3 py-1 text-xs font-semibold text-neza-muted hover:border-neza-wine hover:text-neza-wine">Teléfono</button><button type="button" data-etiqueta="Fecha" data-tipo="DATE" class="rounded-full border border-neza-line bg-white px-3 py-1 text-xs font-semibold text-neza-muted hover:border-neza-wine hover:text-neza-wine">Fecha</button><button type="button" data-etiqueta="Documento" data-tipo="FILE" class="rounded-full border border-neza-line bg-white px-3 py-1 text-xs font-semibold text-neza-muted hover:border-neza-wine hover:text-neza-wine">Documento</button></div></div>
                            <div><label for="etiqueta-{{ $seccion->id }}" class="block text-xs font-semibold text-neza-muted">Etiqueta del campo</label><input id="etiqueta-{{ $seccion->id }}" name="etiqueta" required maxlength="200" placeholder="Ej. CURP" class="mt-1 w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"></div>
                            <div><label for="tipo-{{ $seccion->id }}" class="block text-xs font-semibold text-neza-muted">Tipo</label><select id="tipo-{{ $seccion->id }}" name="tipo" data-tipo-campo class="mt-1 w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft">@foreach ($tiposCampo as $valor => $nombre)<option value="{{ $valor }}" @selected(old('tipo') === $valor)>{{ $nombre }}</option>@endforeach</select></div>
                            <label class="flex items-center gap-2 rounded-lg bg-white px-3 py-2.5 text-sm text-neza-ink"><input type="checkbox" name="requerido" value="1" class="size-4 rounded border-neza-line text-neza-wine focus:ring-neza-wine"> Obligatorio</label>
                            <button type="submit" class="rounded-lg border border-neza-wine px-4 py-2.5 text-sm font-semibold text-neza-wine hover:bg-neza-wine-soft">Agregar campo</button>
                            <div class="hidden md:col-span-2" data-opciones-builder>
                                <label for="opcion-{{ $seccion->id }}" class="block text-xs font-semibold text-neza-muted">Opciones</label>
                                <div class="mt-1 flex gap-2"><input id="opcion-{{ $seccion->id }}" type="text" data-opcion-input placeholder="Escribe una opción y presiona Enter" class="min-w-0 flex-1 rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"><button type="button" data-agregar-opcion class="rounded-lg border border-neza-wine px-3 py-2 text-sm font-semibold text-neza-wine hover:bg-neza-wine-soft">Agregar</button></div>
                                <div class="mt-2 flex flex-wrap gap-2" data-opciones-lista></div>
                                <input type="hidden" name="opciones" value="{{ old('opciones') }}" data-opciones-valor>
                                <p class="mt-2 text-xs text-neza-muted">Presiona Enter para agregar cada opción.</p>
                                @error('opciones')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                            </div>
                        </form>
                    @endif
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-neza-line bg-white px-6 py-14 text-center"><h3 class="font-semibold text-neza-ink">Comienza agregando una sección</h3><p class="mt-2 text-sm text-neza-muted">Por ejemplo: datos del solicitante, ubicación del predio o documentos requeridos.</p></div>
            @endforelse
        </section>
        @if ($esBorrador && $camposVersion->isNotEmpty())
            <section id="reglas" class="scroll-mt-24 rounded-xl border border-neza-line bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-neza-ink">Grupos de campos</h2>
                <p class="mt-1 text-sm text-neza-muted">Agrupa varias preguntas para aplicar una sola condición a todas ellas.</p>
                <form action="{{ route('formulario.grupos-campos.store', $formulario) }}" method="POST" class="mt-5">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto]">
                        <div><label for="titulo-grupo" class="block text-sm font-semibold text-neza-ink">Nombre del grupo</label><input id="titulo-grupo" name="titulo" required maxlength="200" placeholder="Ej. Datos de persona moral" class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft">@error('titulo')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <button type="submit" class="self-end rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Crear grupo</button>
                    </div>
                    <fieldset class="mt-4 grid gap-2 sm:grid-cols-2"><legend class="text-sm font-semibold text-neza-ink">Campos incluidos</legend>@forelse ($camposVersion->filter(fn ($campo) => $campo->gruposCampos->isEmpty()) as $campo)<label class="flex items-center gap-2 rounded-lg border border-neza-line px-3 py-2 text-sm text-neza-ink"><input type="checkbox" name="campo_ids[]" value="{{ $campo->id }}" class="size-4 rounded border-neza-line text-neza-wine focus:ring-neza-wine"> {{ $campo->etiqueta }}</label>@empty<p class="text-sm text-neza-muted sm:col-span-2">Todos los campos ya pertenecen a un grupo. Retira uno de su grupo actual para poder usarlo aquí.</p>@endforelse</fieldset>
                    @error('campo_ids')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                </form>
                @foreach ($version->gruposCampos->sortBy('orden') as $grupo)
                    <details class="mt-5 rounded-lg border border-neza-line p-4">
                        <summary class="cursor-pointer font-semibold text-neza-ink">{{ $grupo->titulo }} <span class="ml-1 text-sm font-normal text-neza-muted">({{ $grupo->campos->count() }} campos)</span></summary>
                        <ul class="mt-3 divide-y divide-neza-line rounded-lg border border-neza-line">
                            @forelse ($grupo->campos->sortBy('orden') as $campo)
                                <li class="flex items-center justify-between gap-3 px-3 py-2 text-sm text-neza-ink"><span>{{ $campo->etiqueta }}</span><form action="{{ route('formulario.grupos-campos.campos.destroy', [$formulario, $grupo, $campo]) }}" method="POST">@csrf @method('DELETE')<button type="submit" class="text-xs font-semibold text-rose-700 hover:underline">Quitar del grupo</button></form></li>
                            @empty
                                <li class="px-3 py-2 text-sm text-neza-muted">Este grupo aún no tiene campos.</li>
                            @endforelse
                        </ul>
                        @if ($grupo->condicionesDestino->isNotEmpty())<ul class="mt-3 space-y-2 text-xs text-neza-muted">@foreach ($grupo->condicionesDestino as $condicion)<li class="flex items-center justify-between gap-3 rounded-lg border border-neza-line bg-neza-canvas px-3 py-2"><span>{{ $condicion->accion }} si {{ $condicion->campoOrigen->etiqueta }} {{ $condicion->operador }}{{ data_get($condicion->valor_comparacion, 'valor') ? ' '.data_get($condicion->valor_comparacion, 'valor') : '' }}</span><form action="{{ route('formulario.condiciones.destroy', [$formulario, $condicion]) }}" method="POST">@csrf @method('DELETE')<button type="submit" class="shrink-0 font-semibold text-rose-700 hover:underline">Eliminar condición</button></form></li>@endforeach</ul>@endif
                        <form action="{{ route('formulario.grupos-campos.condiciones.store', [$formulario, $grupo]) }}" method="POST" class="mt-4 grid gap-3 md:grid-cols-4">
                            @csrf
                            <div><label class="block text-xs font-semibold text-neza-muted">Campo origen</label><select name="campo_origen_id" required class="mt-1 w-full rounded-lg border border-neza-line bg-white px-3 py-2 text-sm">@foreach ($camposVersion as $origen)<option value="{{ $origen->id }}">{{ $origen->etiqueta }}</option>@endforeach</select></div>
                            <div><label class="block text-xs font-semibold text-neza-muted">Operador</label><select name="operador" class="mt-1 w-full rounded-lg border border-neza-line bg-white px-3 py-2 text-sm"><option value="EQUALS">Es igual a</option><option value="NOT_EQUALS">No es igual a</option><option value="CONTAINS">Contiene</option><option value="GREATER_THAN">Mayor que</option><option value="LESS_THAN">Menor que</option><option value="IS_EMPTY">Está vacío</option><option value="IS_NOT_EMPTY">No está vacío</option></select></div>
                            <div><label class="block text-xs font-semibold text-neza-muted">Valor esperado</label><input name="valor" maxlength="200" placeholder="Ej. Sí" class="mt-1 w-full rounded-lg border border-neza-line px-3 py-2 text-sm"></div>
                            <div><label class="block text-xs font-semibold text-neza-muted">Acción</label><select name="accion" class="mt-1 w-full rounded-lg border border-neza-line bg-white px-3 py-2 text-sm"><option value="SHOW">Mostrar grupo</option><option value="HIDE">Ocultar grupo</option><option value="REQUIRE">Hacer obligatorio</option><option value="OPTIONAL">Hacer opcional</option></select><button type="submit" class="mt-3 w-full rounded-lg border border-neza-wine px-3 py-2 text-sm font-semibold text-neza-wine hover:bg-neza-wine-soft">Guardar condición</button></div>
                        </form>
                    </details>
                @endforeach
            </section>
        @endif
        <section id="revision" class="scroll-mt-24 rounded-xl border border-neza-line bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-neza-ink">Revisión del borrador</h2>
            <p class="mt-1 text-sm text-neza-muted">Confirma estos puntos antes de compartir el formulario para su publicación.</p>
            <ul class="mt-4 grid gap-3 sm:grid-cols-3">
                <li class="rounded-lg border px-4 py-3 text-sm {{ $version->secciones->isNotEmpty() ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800' }}">{{ $version->secciones->isNotEmpty() ? 'Listo' : 'Pendiente' }}: al menos una sección.</li>
                <li class="rounded-lg border px-4 py-3 text-sm {{ $camposTotales > 0 ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800' }}">{{ $camposTotales > 0 ? "Listo: {$camposTotales} preguntas" : 'Pendiente: agrega preguntas.' }}</li>
                <li class="rounded-lg border px-4 py-3 text-sm {{ $seccionesVacias === 0 && $version->secciones->isNotEmpty() ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800' }}">{{ $seccionesVacias === 0 && $version->secciones->isNotEmpty() ? 'Listo: no hay secciones vacías.' : "Pendiente: {$seccionesVacias} sección(es) sin preguntas." }}</li>
            </ul>
        </section>
    </div>
@endsection
