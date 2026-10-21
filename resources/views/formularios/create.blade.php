@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <a href="{{ route('formulario.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-neza-muted hover:text-neza-wine">
                <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                Volver a formularios
            </a>
            <p class="mt-5 text-sm font-semibold text-neza-wine">Formularios</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-neza-ink">Crear formulario</h1>
            <p class="mt-2 text-sm text-neza-muted">Registra la información general para iniciar la primera versión del formulario.</p>
        </div>

        <form action="{{ route('formulario.store') }}" method="POST" class="space-y-5">
            @csrf
            <section class="rounded-xl border border-neza-line bg-white p-6 shadow-sm">
                <div class="border-b border-neza-line pb-5">
                    <h2 class="text-lg font-bold text-neza-ink">Información general</h2>
                    <p class="mt-1 text-sm text-neza-muted">Estos datos identificarán el formulario dentro del catálogo administrativo.</p>
                </div>
                <div class="mt-6 space-y-5">
                    <div>
                        <label for="nombre" class="block text-sm font-semibold text-neza-ink">Nombre del formulario <span class="text-rose-700">*</span></label>
                        <input id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" required maxlength="200" autofocus placeholder="Ej. Solicitud de licencia de construcción"
                            class="mt-2 block w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm text-neza-ink placeholder:text-neza-muted focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('nombre') border-rose-600 @enderror">
                        @error('nombre')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="departamento_nombre" class="block text-sm font-semibold text-neza-ink">Departamento responsable <span class="text-rose-700">*</span></label>
                            <input id="departamento_nombre" name="departamento_nombre" type="text" list="departamentos-accesos" value="{{ old('departamento_nombre') }}" required autocomplete="off" data-departamento-input placeholder="Escribe para buscar un departamento"
                                class="mt-2 block w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm text-neza-ink placeholder:text-neza-muted focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('departamento_accesos_id') border-rose-600 @enderror">
                            <input type="hidden" name="departamento_accesos_id" value="{{ old('departamento_accesos_id') }}" data-departamento-id>
                            <datalist id="departamentos-accesos">
                                @foreach (collect($departamentos)->whereNull('parent_id') as $departamento)
                                    <option value="{{ $departamento['nombre'] }}{{ $departamento['siglas'] ? ' ('.$departamento['siglas'].')' : '' }}" data-departamento-id="{{ $departamento['id'] }}"></option>
                                @endforeach
                            </datalist>
                            <p class="mt-2 text-xs text-neza-muted">Catálogo obtenido de Accesos.</p>
                            @if (empty($departamentos))<p class="mt-2 text-sm text-rose-700">No se pudo cargar el catálogo de departamentos. Recarga la página para intentarlo de nuevo.</p>@endif
                            @error('departamento_accesos_id')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div id="subdepartamento-contenedor" class="hidden">
                        <label for="subdepartamento_nombre" class="block text-sm font-semibold text-neza-ink">Subdepartamento</label>
                        <input id="subdepartamento_nombre" name="subdepartamento_nombre" type="text" list="subdepartamentos-accesos" value="{{ old('subdepartamento_nombre') }}" autocomplete="off" data-subdepartamento-input placeholder="Selecciona un subdepartamento"
                            class="mt-2 block w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm text-neza-ink placeholder:text-neza-muted focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('subdepartamento_accesos_id') border-rose-600 @enderror">
                        <input type="hidden" name="subdepartamento_accesos_id" value="{{ old('subdepartamento_accesos_id') }}" data-subdepartamento-id>
                        <datalist id="subdepartamentos-accesos"></datalist>
                        <datalist id="subdepartamentos-origen">
                            @foreach (collect($departamentos)->whereNotNull('parent_id') as $subdepartamento)
                                <option value="{{ $subdepartamento['nombre'] }}{{ $subdepartamento['siglas'] ? ' ('.$subdepartamento['siglas'].')' : '' }}" data-subdepartamento-id="{{ $subdepartamento['id'] }}" data-parent-id="{{ $subdepartamento['parent_id'] }}"></option>
                            @endforeach
                        </datalist>
                        @error('subdepartamento_accesos_id')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="descripcion" class="block text-sm font-semibold text-neza-ink">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="4" maxlength="5000" placeholder="Describe el propósito o alcance del formulario."
                            class="mt-2 block w-full resize-y rounded-lg border border-neza-line px-3 py-2.5 text-sm text-neza-ink placeholder:text-neza-muted focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('descripcion') border-rose-600 @enderror">{{ old('descripcion') }}</textarea>
                        <p class="mt-2 text-xs text-neza-muted">Opcional. Podrás completar secciones, campos y documentos requeridos en la versión borrador.</p>
                        @error('descripcion')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-neza-wine-soft bg-neza-wine-soft p-5">
                <div class="flex gap-3">
                    <svg class="mt-0.5 size-5 shrink-0 text-neza-wine" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 9.75h1.5v1.5h-1.5v-1.5Zm0 3h1.5v3h-1.5v-3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75a8.25 8.25 0 1 0 0 16.5 8.25 8.25 0 0 0 0-16.5Z" /></svg>
                    <div><h2 class="font-semibold text-neza-ink">Se creará como borrador</h2><p class="mt-1 text-sm text-neza-muted">Al guardar se genera la versión 1 con estado Borrador, ligada al trámite y departamento seleccionados. Podrás configurarla antes de publicarla.</p></div>
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 border-t border-neza-line pt-5 sm:flex-row sm:justify-end">
                <a href="{{ route('formulario.index') }}" class="inline-flex justify-center rounded-lg border border-neza-line px-4 py-2.5 text-sm font-semibold text-neza-muted transition hover:bg-neza-canvas">Cancelar</a>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                    <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 10.5 18l9-12" /></svg>
                    Crear borrador
                </button>
            </div>
        </form>
    </div>
@endsection
