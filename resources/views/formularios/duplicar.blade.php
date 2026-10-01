@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('formulario.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-neza-muted hover:text-neza-wine">
            <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
            Volver a formularios
        </a>

        <div class="mt-6">
            <p class="text-sm font-semibold text-neza-wine">Duplicar formulario</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-neza-ink">Crear una copia para otro trámite</h1>
            <p class="mt-2 text-sm text-neza-muted">Se copiará la configuración de “{{ $formulario->nombre }}” a un nuevo borrador.</p>
        </div>

        <form action="{{ route('formulario.duplicar.store', $formulario) }}" method="POST" class="mt-6 space-y-5">
            @csrf
            <section class="rounded-xl border border-neza-line bg-white p-6 shadow-sm">
                <label for="tramite_id" class="block text-sm font-semibold text-neza-ink">Trámite destino <span class="text-rose-700">*</span></label>
                <select id="tramite_id" name="tramite_id" required class="mt-2 block w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm text-neza-ink focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('tramite_id') border-rose-600 @enderror">
                    <option value="">Selecciona un trámite</option>
                    @foreach ($tramites as $tramite)
                        <option value="{{ $tramite->id }}" @selected((string) old('tramite_id') === (string) $tramite->id)>{{ $tramite->nombre }}</option>
                    @endforeach
                </select>
                @error('tramite_id')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                @if ($tramites->isEmpty())
                    <p class="mt-2 text-sm text-neza-muted">No hay trámites disponibles: todos los trámites activos ya tienen un formulario asignado.</p>
                @endif
            </section>

            <section class="rounded-xl border border-neza-line bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-neza-ink">Departamento responsable</h2>
                <p class="mt-1 text-sm text-neza-muted">Puedes conservar o cambiar el departamento y subdepartamento de la copia.</p>
                <div class="mt-5 space-y-5">
                    <div>
                        <label for="departamento_nombre" class="block text-sm font-semibold text-neza-ink">Departamento <span class="text-rose-700">*</span></label>
                        <input id="departamento_nombre" name="departamento_nombre" type="text" list="departamentos-accesos" value="{{ old('departamento_nombre', $formulario->departamento_nombre_snapshot) }}" required autocomplete="off" data-departamento-input placeholder="Escribe para buscar un departamento"
                            class="mt-2 block w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm text-neza-ink placeholder:text-neza-muted focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('departamento_accesos_id') border-rose-600 @enderror">
                        <input type="hidden" name="departamento_accesos_id" value="{{ old('departamento_accesos_id', $formulario->departamento_accesos_id) }}" data-departamento-id>
                        <datalist id="departamentos-accesos">
                            @foreach (collect($departamentos)->whereNull('parent_id') as $departamento)
                                <option value="{{ $departamento['nombre'] }}{{ $departamento['siglas'] ? ' ('.$departamento['siglas'].')' : '' }}" data-departamento-id="{{ $departamento['id'] }}"></option>
                            @endforeach
                        </datalist>
                        @if (empty($departamentos))<p class="mt-2 text-sm text-rose-700">No se pudo cargar el catálogo de departamentos. Recarga la página para intentarlo de nuevo.</p>@endif
                        @error('departamento_accesos_id')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div id="subdepartamento-contenedor" class="hidden">
                        <label for="subdepartamento_nombre" class="block text-sm font-semibold text-neza-ink">Subdepartamento</label>
                        <input id="subdepartamento_nombre" name="subdepartamento_nombre" type="text" list="subdepartamentos-accesos" value="{{ old('subdepartamento_nombre', $formulario->subdepartamento_nombre_snapshot) }}" autocomplete="off" data-subdepartamento-input placeholder="Selecciona un subdepartamento"
                            class="mt-2 block w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm text-neza-ink placeholder:text-neza-muted focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('subdepartamento_accesos_id') border-rose-600 @enderror">
                        <input type="hidden" name="subdepartamento_accesos_id" value="{{ old('subdepartamento_accesos_id', $formulario->subdepartamento_accesos_id) }}" data-subdepartamento-id>
                        <datalist id="subdepartamentos-accesos"></datalist>
                        <datalist id="subdepartamentos-origen">
                            @foreach (collect($departamentos)->whereNotNull('parent_id') as $subdepartamento)
                                <option value="{{ $subdepartamento['nombre'] }}{{ $subdepartamento['siglas'] ? ' ('.$subdepartamento['siglas'].')' : '' }}" data-subdepartamento-id="{{ $subdepartamento['id'] }}" data-parent-id="{{ $subdepartamento['parent_id'] }}"></option>
                            @endforeach
                        </datalist>
                        @error('subdepartamento_accesos_id')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-neza-wine-soft bg-neza-wine-soft p-5">
                <p class="text-sm text-neza-ink">La copia conservará secciones, campos, opciones, condiciones, grupos y requisitos; se creará como versión 1 en estado Borrador.</p>
            </section>

            <div class="flex flex-col-reverse gap-3 border-t border-neza-line pt-5 sm:flex-row sm:justify-end">
                <a href="{{ route('formulario.index') }}" class="inline-flex justify-center rounded-lg border border-neza-line px-4 py-2.5 text-sm font-semibold text-neza-muted transition hover:bg-neza-canvas">Cancelar</a>
                <button type="submit" @disabled($tramites->isEmpty()) class="inline-flex items-center justify-center gap-2 rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                    <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75" /></svg>
                    Duplicar formulario
                </button>
            </div>
        </form>
    </div>
@endsection
