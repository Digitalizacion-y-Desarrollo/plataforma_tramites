@php
    $valor = fn (string $campo, mixed $predeterminado = null) => old('_modal') === $modo ? old($campo, $predeterminado) : $predeterminado;
    $modalidadesBase = (array) $valor('modalidades', $tramite?->modalidades ?? []);
    $modalidadSeleccionada = old('_modal') === $modo
        ? old('modalidad', null)
        : (count($modalidadesBase) > 1 ? 'MIXTO' : ($modalidadesBase[0] ?? null));
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    <div><label for="{{ $modo }}-duracion" class="block text-sm font-semibold text-neza-ink">Duración</label><input id="{{ $modo }}-duracion" name="duracion" value="{{ $valor('duracion', $tramite?->duracion) }}" maxlength="100" placeholder="Ej. Vigencia de un año" class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm text-neza-ink"></div>
    <div><label for="{{ $modo }}-costo" class="block text-sm font-semibold text-neza-ink">Costo</label><input id="{{ $modo }}-costo" name="costo" value="{{ $valor('costo', $tramite?->costo) }}" maxlength="100" placeholder="Ej. Gratuito o $350.00" class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm text-neza-ink"></div>
    <div class="sm:col-span-2"><label for="{{ $modo }}-tiempo" class="block text-sm font-semibold text-neza-ink">Tiempo estimado</label><input id="{{ $modo }}-tiempo" name="tiempo_estimado" value="{{ $valor('tiempo_estimado', $tramite?->tiempo_estimado) }}" maxlength="100" placeholder="Ej. 5 a 10 días hábiles" class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm text-neza-ink"></div>
    <div class="sm:col-span-2" data-expiracion-contenedor><label for="{{ $modo }}-expira" class="block text-sm font-semibold text-neza-ink">Tiempo de expiración de descarga <span class="font-normal text-neza-muted">(días, opcional)</span></label><p class="mt-1 text-xs text-neza-muted">Solo aplica a trámites en línea o mixtos. Vacío = descarga sin vencimiento. Ej. 30 días.</p><input id="{{ $modo }}-expira" name="descarga_expira_dias" type="number" min="1" max="3650" step="1" value="{{ $valor('descarga_expira_dias', $tramite?->descarga_expira_dias) }}" placeholder="Ej. 30" class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm text-neza-ink @error('descarga_expira_dias') border-rose-600 @enderror">@error('descarga_expira_dias')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
</div>

<fieldset><legend class="text-sm font-semibold text-neza-ink">Modalidad</legend><p class="mt-1 text-xs text-neza-muted">Elige una modalidad.</p><div class="mt-3 flex flex-wrap gap-4"><label class="flex items-center gap-2 text-sm text-neza-ink"><input type="radio" name="modalidad" value="EN_LINEA" @checked($modalidadSeleccionada === 'EN_LINEA') class="size-4 border-neza-line text-neza-wine focus:ring-neza-wine"> En línea</label><label class="flex items-center gap-2 text-sm text-neza-ink"><input type="radio" name="modalidad" value="PRESENCIAL" @checked($modalidadSeleccionada === 'PRESENCIAL') class="size-4 border-neza-line text-neza-wine focus:ring-neza-wine"> Presencial</label><label class="flex items-center gap-2 text-sm text-neza-ink"><input type="radio" name="modalidad" value="MIXTO" @checked($modalidadSeleccionada === 'MIXTO') class="size-4 border-neza-line text-neza-wine focus:ring-neza-wine"> Mixto</label></div>@error('modalidad')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</fieldset>

@foreach (['requisitos' => ['Requisitos', 'Agrega un requisito y presiona Enter'], 'proceso' => ['Proceso', 'Agrega un paso y presiona Enter']] as $campo => [$etiqueta, $ayuda])
    @php($items = array_values((array) $valor($campo, $tramite?->{$campo} ?? [])))
    <section data-list-builder data-list-name="{{ $campo }}" data-list-inicial='@json($items)'>
        <label for="{{ $modo }}-{{ $campo }}" class="block text-sm font-semibold text-neza-ink">{{ $etiqueta }}</label><p class="mt-1 text-xs text-neza-muted">{{ $ayuda }}.</p>
        <div class="mt-2 flex gap-2"><input id="{{ $modo }}-{{ $campo }}" data-list-input type="text" maxlength="500" placeholder="{{ $ayuda }}" class="min-w-0 flex-1 rounded-lg border border-neza-line px-3 py-2.5 text-sm"><button type="button" data-list-add class="rounded-lg border border-neza-wine px-3 text-sm font-semibold text-neza-wine hover:bg-neza-wine-soft">Agregar</button></div>
        <ul data-list-items class="mt-3 space-y-2"></ul><div data-list-hidden></div>
    </section>
@endforeach

@php($preguntas = array_values((array) $valor('preguntas_frecuentes', $tramite?->preguntas_frecuentes ?? [])))
<section data-faq-builder data-faq-inicial='@json($preguntas)'>
    <h3 class="text-sm font-semibold text-neza-ink">Preguntas frecuentes</h3><p class="mt-1 text-xs text-neza-muted">Agrega cada pregunta con su respuesta; se mostrarán al público en un acordeón.</p>
    <div class="mt-3 grid gap-2"><input data-faq-question type="text" maxlength="500" placeholder="Pregunta" class="rounded-lg border border-neza-line px-3 py-2.5 text-sm"><textarea data-faq-answer rows="3" maxlength="2000" placeholder="Respuesta" class="resize-y rounded-lg border border-neza-line px-3 py-2.5 text-sm"></textarea><button type="button" data-faq-add class="justify-self-start rounded-lg border border-neza-wine px-3 py-2 text-sm font-semibold text-neza-wine hover:bg-neza-wine-soft">Agregar pregunta</button></div>
    <div data-faq-items class="mt-3 space-y-2"></div><div data-faq-hidden></div>
</section>
