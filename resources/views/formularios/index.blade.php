@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-[1440px] space-y-5">
        @if (session('success'))
            <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                role="status">
                <svg class="size-5 shrink-0" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                {{ session('success') }}
            </div>
        @endif
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-neza-wine">Administración</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-neza-ink">Listado de formularios</h1>
                <p class="mt-2 text-sm text-neza-muted">Gestiona las versiones y disponibilidad de los formularios
                    municipales.</p>
            </div>
            <a href="{{ route('formulario.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo formulario
            </a>
        </section>

        <form
            class="grid gap-3 rounded-xl border border-neza-line bg-white p-4 shadow-sm lg:grid-cols-[minmax(0,2fr)_repeat(4,minmax(0,1fr))_auto]"
            action="{{ route('formulario.index') }}" method="GET">
            <label class="block"><span class="sr-only">Buscar formularios</span><span class="relative block"><span
                        class="pointer-events-none absolute inset-y-0 left-3 flex items-center"><svg
                            class="size-5 text-neza-muted" aria-hidden="true" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" />
                        </svg></span><input type="search" name="buscar"
                        placeholder="Buscar por nombre, trámite o departamento"
                        class="w-full rounded-lg border border-neza-line bg-neza-canvas py-2.5 pl-10 pr-3 text-sm text-neza-ink placeholder:text-neza-muted focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"></span></label>
            @foreach (['Estado' => 'Todos', 'Trámite' => 'Todos', 'Departamento' => 'Todos', 'Tipo' => 'Todos'] as $etiqueta => $valor)
                <label class="block text-xs font-medium text-neza-muted">{{ $etiqueta }}<select
                        class="mt-1 w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm text-neza-ink focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft">
                        <option>{{ $valor }}</option>
                    </select></label>
            @endforeach
            <button type="reset"
                class="self-end rounded-lg border border-neza-line px-4 py-2.5 text-sm font-semibold text-neza-muted transition hover:bg-neza-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Limpiar
                filtros</button>
        </form>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_280px]">
            <section class="overflow-hidden rounded-xl border border-neza-line bg-white shadow-sm">
                <div
                    class="flex flex-col gap-4 border-b border-neza-line px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-bold text-neza-ink">Formularios</h2>
                        <p class="mt-1 text-xs text-neza-muted">Consulta y administra las versiones configuradas.</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 border-b border-neza-line px-5 py-3">
                    <a class="rounded-md px-3 py-1.5 text-xs font-semibold {{ $filtro === '' ? 'bg-neza-wine text-white' : 'bg-neza-wine-soft text-neza-wine hover:bg-neza-wine hover:text-white' }}"
                        href="{{ route('formulario.index') }}">Todos <span
                            class="ml-1 opacity-80">({{ $conteos['todos'] }})</span></a>
                    <a class="rounded-md px-3 py-1.5 text-xs font-semibold {{ $filtro === 'RECIENTES' ? 'bg-rose-700 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}"
                        href="{{ route('formulario.index', ['filtro' => 'RECIENTES']) }}">Con cambios recientes <span class="ml-1 opacity-80">({{ $conteos['RECIENTES'] }})</span></a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[780px] text-left text-sm">
                        <thead class="bg-neza-canvas text-xs font-semibold text-neza-muted">
                            <tr>
                                <th class="px-5 py-3">Formulario</th>
                                <th class="px-4 py-3">Trámite</th>
                                <th class="px-4 py-3">Departamento</th>
                                <th class="px-4 py-3 text-center">Campos</th>
                                <th class="px-4 py-3">Última edición</th>
                                <th class="px-5 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neza-line">
                            @forelse ($formularios as $formulario)
                                @php
                                    $version = $formulario->ultimaVersion;
                                    $campos = $version
                                        ? $version->secciones->sum(fn($seccion) => $seccion->campos->count())
                                        : 0;
                                    $tramites = $version
                                        ? ($version->tramites
                                            ->where('pivot.activo', true)
                                            ->pluck('nombre')
                                            ->join(', ') ?:
                                        'Sin trámite')
                                        : 'Sin trámite';
                                @endphp
                                <tr class="hover:bg-neza-canvas">
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-neza-ink">{{ $formulario->nombre }}</p>
                                        <p class="mt-1 text-xs text-neza-muted">Versión
                                            {{ $version?->numero_version ?? '—' }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-neza-muted">{{ $tramites }}</td>
                                    <td class="px-4 py-4 text-neza-muted">
                                        {{ $formulario->departamento_nombre_snapshot ?? 'Sin departamento' }}@if ($formulario->subdepartamento_nombre_snapshot)
                                            <span
                                                class="block text-xs">{{ $formulario->subdepartamento_nombre_snapshot }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-center text-neza-muted">{{ $campos }}</td>
                                    <td class="px-4 py-4 text-neza-muted">
                                        {{ $formulario->updated_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-3"><a
                                                href="{{ route('formulario.show', $formulario) }}" title="Ver formulario"
                                                class="text-sm font-semibold text-neza-muted hover:text-blue-600 hover:underline">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor" class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg>
                                            </a>
                                            <a href="{{ route('formulario.duplicar', $formulario) }}"
                                                title="Duplicar formulario"
                                                class="text-sm font-semibold text-neza-muted hover:text-indigo-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" />
                                                </svg>
                                            </a>
                                            <a href="{{ route('formulario.edit', $formulario) }}"
                                                title="Editar formulario"
                                                class="text-sm font-semibold text-neza-muted hover:text-neza-wine">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                            </a>
                                            <form action="{{ route('formulario.destroy', $formulario) }}" method="POST"
                                                data-confirm-delete data-confirm-title="¿Eliminar formulario?"
                                                data-confirm-text="El formulario se ocultará del listado, pero su información se conservará."
                                                class="inline-flex">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Eliminar formulario" aria-label="Eliminar formulario"
                                                    class="text-sm font-semibold text-neza-muted hover:text-rose-700">
                                                <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                                </button>
                                            </form>


                                        </div>
                                    </td>
                                </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-16 text-center">
                                            <div class="mx-auto flex max-w-sm flex-col items-center"><span
                                                    class="grid size-12 place-items-center rounded-full bg-neza-wine-soft text-neza-wine"><svg
                                                        class="size-6" aria-hidden="true" fill="none" viewBox="0 0 24 24"
                                                        stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5V6.75a3.375 3.375 0 0 0-3.375-3.375h-3.75A3.375 3.375 0 0 0 4.125 6.75v10.5a3.375 3.375 0 0 0 3.375 3.375H12" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M16.5 14.25h3.75m0 0V18m0-3.75-4.5 4.5" />
                                                    </svg></span>
                                                <h3 class="mt-4 font-semibold text-neza-ink">Aún no hay formularios</h3>
                                                <p class="mt-1 text-sm text-neza-muted">Crea el primer formulario para comenzar
                                                    a configurar los trámites.</p><a href="{{ route('formulario.create') }}"
                                                    class="mt-4 text-sm font-semibold text-neza-wine hover:underline">Crear
                                                    formulario</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="flex items-center justify-between border-t border-neza-line px-5 py-4 text-xs text-neza-muted">
                        <span>Mostrando {{ $formularios->count() }} de {{ $formularios->total() }} resultados</span>
                        <div class="flex gap-2">
                            @if ($formularios->onFirstPage())
                            <span class="rounded-md border border-neza-line px-2 py-1 opacity-50">Anterior</span>@else<a
                                    href="{{ $formularios->previousPageUrl() }}"
                                    class="rounded-md border border-neza-line px-2 py-1 hover:bg-neza-canvas">Anterior</a>
                                @endif @if ($formularios->hasMorePages())
                                    <a href="{{ $formularios->nextPageUrl() }}"
                                    class="rounded-md border border-neza-line px-2 py-1 hover:bg-neza-canvas">Siguiente</a>@else<span
                                        class="rounded-md border border-neza-line px-2 py-1 opacity-50">Siguiente</span>
                                @endif
                        </div>
                    </div>
                </section>
                <aside class="space-y-5">
                    <section class="rounded-xl border border-neza-line bg-white p-5 shadow-sm">
                        <h2 class="text-base font-bold text-neza-ink">Resumen rápido</h2>
                        <dl class="mt-4 divide-y divide-neza-line">
                            <div class="flex items-center justify-between py-3">
                                <dt class="text-sm text-neza-muted">Publicados</dt>
                                <dd class="text-lg font-bold text-emerald-700">0</dd>
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <dt class="text-sm text-neza-muted">Borradores</dt>
                                <dd class="text-lg font-bold text-amber-700">0</dd>
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <dt class="text-sm text-neza-muted">Archivados</dt>
                                <dd class="text-lg font-bold text-slate-600">0</dd>
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <dt class="text-sm text-neza-muted">Asignados a trámites</dt>
                                <dd class="text-lg font-bold text-neza-wine">0</dd>
                            </div>
                        </dl>
                    </section>
                    <section class="rounded-xl border border-neza-line bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-base font-bold text-neza-ink">Actividad reciente</h2><a href="#"
                                class="text-xs font-semibold text-neza-wine hover:underline">Ver todo</a>
                        </div>
                        <p
                            class="mt-5 rounded-lg border border-dashed border-neza-line px-4 py-6 text-center text-sm text-neza-muted">
                            No hay actividad reciente.</p>
                    </section>
                    <section class="rounded-xl border border-neza-line bg-white p-5 shadow-sm">
                        <h2 class="text-base font-bold text-neza-ink">Campos más usados</h2>
                        <p class="mt-4 text-sm text-neza-muted">Aún no hay campos registrados.</p>
                    </section>
                </aside>
            </div>
        </div>
    @endsection
