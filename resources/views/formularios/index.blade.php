@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-[1440px] space-y-5">
        @if (session('success'))
            <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                <svg class="size-5 shrink-0" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                {{ session('success') }}
            </div>
        @endif
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-neza-wine">Administración</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-neza-ink">Listado de formularios</h1>
                <p class="mt-2 text-sm text-neza-muted">Gestiona las versiones y disponibilidad de los formularios municipales.</p>
            </div>
            <a href="{{ route('formulario.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Nuevo formulario
            </a>
        </section>

        <form class="grid gap-3 rounded-xl border border-neza-line bg-white p-4 shadow-sm lg:grid-cols-[minmax(0,2fr)_repeat(4,minmax(0,1fr))_auto]" action="{{ route('formulario.index') }}" method="GET">
            <label class="block"><span class="sr-only">Buscar formularios</span><span class="relative block"><span class="pointer-events-none absolute inset-y-0 left-3 flex items-center"><svg class="size-5 text-neza-muted" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" /></svg></span><input type="search" name="buscar" placeholder="Buscar por nombre, trámite o departamento" class="w-full rounded-lg border border-neza-line bg-neza-canvas py-2.5 pl-10 pr-3 text-sm text-neza-ink placeholder:text-neza-muted focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"></span></label>
            @foreach (['Estado' => 'Todos', 'Trámite' => 'Todos', 'Departamento' => 'Todos', 'Tipo' => 'Todos'] as $etiqueta => $valor)
                <label class="block text-xs font-medium text-neza-muted">{{ $etiqueta }}<select class="mt-1 w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm text-neza-ink focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"><option>{{ $valor }}</option></select></label>
            @endforeach
            <button type="reset" class="self-end rounded-lg border border-neza-line px-4 py-2.5 text-sm font-semibold text-neza-muted transition hover:bg-neza-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Limpiar filtros</button>
        </form>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_280px]">
            <section class="overflow-hidden rounded-xl border border-neza-line bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-neza-line px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-base font-bold text-neza-ink">Formularios</h2><p class="mt-1 text-xs text-neza-muted">Consulta y administra las versiones configuradas.</p></div><p class="text-xs text-neza-muted">Última actualización: hoy, 13:10</p></div>
                <div class="flex flex-wrap gap-2 border-b border-neza-line px-5 py-3">
                    <a class="rounded-md bg-neza-wine px-3 py-1.5 text-xs font-semibold text-white" href="{{ route('formulario.index') }}">Todos <span class="ml-1 opacity-80">({{ $formularios->total() }})</span></a>
                    <a class="rounded-md bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100" href="#">Publicados <span class="ml-1 opacity-80">(0)</span></a>
                    <a class="rounded-md bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100" href="#">Borradores <span class="ml-1 opacity-80">(0)</span></a>
                    <a class="rounded-md bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-200" href="#">Archivados <span class="ml-1 opacity-80">(0)</span></a>
                    <a class="rounded-md bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100" href="#">Con cambios recientes <span class="ml-1 opacity-80">(0)</span></a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[780px] text-left text-sm">
                        <thead class="bg-neza-canvas text-xs font-semibold text-neza-muted">
                            <tr><th class="px-5 py-3">Formulario</th><th class="px-4 py-3">Trámite</th><th class="px-4 py-3">Departamento</th><th class="px-4 py-3 text-center">Campos</th><th class="px-4 py-3">Última edición</th><th class="px-4 py-3">Estado</th><th class="px-5 py-3 text-right">Acciones</th></tr>
                        </thead>
                        <tbody class="divide-y divide-neza-line">
                            @forelse ($formularios as $formulario)
                                @php
                                    $version = $formulario->ultimaVersion;
                                    $campos = $version ? $version->secciones->sum(fn ($seccion) => $seccion->campos->count()) : 0;
                                    $tramites = $version ? ($version->tramites->where('pivot.activo', true)->pluck('nombre')->join(', ') ?: 'Sin trámite') : 'Sin trámite';
                                    $estado = $version?->estado;
                                    $estadoClase = match ($estado?->clave) {
                                        'PUBLICADO' => 'bg-emerald-50 text-emerald-700',
                                        'BORRADOR' => 'bg-amber-50 text-amber-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <tr class="hover:bg-neza-canvas">
                                    <td class="px-5 py-4"><p class="font-semibold text-neza-ink">{{ $formulario->nombre }}</p><p class="mt-1 text-xs text-neza-muted">Versión {{ $version?->numero_version ?? '—' }}</p></td>
                                    <td class="px-4 py-4 text-neza-muted">{{ $tramites }}</td>
                                    <td class="px-4 py-4 text-neza-muted">{{ $formulario->departamento_nombre_snapshot ?? 'Sin departamento' }}@if ($formulario->subdepartamento_nombre_snapshot)<span class="block text-xs">{{ $formulario->subdepartamento_nombre_snapshot }}</span>@endif</td>
                                    <td class="px-4 py-4 text-center text-neza-muted">{{ $campos }}</td>
                                    <td class="px-4 py-4 text-neza-muted">{{ $formulario->updated_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $estadoClase }}">{{ $estado?->nombre ?? 'Sin versión' }}</span></td>
                                    <td class="px-5 py-4"><div class="flex justify-end gap-3"><a href="{{ route('formulario.show', $formulario) }}" class="text-sm font-semibold text-neza-wine hover:underline">Ver formulario</a><a href="{{ route('formulario.edit', $formulario) }}" class="text-sm font-semibold text-neza-muted hover:text-neza-wine">Editar</a></div></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-5 py-16 text-center"><div class="mx-auto flex max-w-sm flex-col items-center"><span class="grid size-12 place-items-center rounded-full bg-neza-wine-soft text-neza-wine"><svg class="size-6" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5V6.75a3.375 3.375 0 0 0-3.375-3.375h-3.75A3.375 3.375 0 0 0 4.125 6.75v10.5a3.375 3.375 0 0 0 3.375 3.375H12" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 14.25h3.75m0 0V18m0-3.75-4.5 4.5" /></svg></span><h3 class="mt-4 font-semibold text-neza-ink">Aún no hay formularios</h3><p class="mt-1 text-sm text-neza-muted">Crea el primer formulario para comenzar a configurar los trámites.</p><a href="{{ route('formulario.create') }}" class="mt-4 text-sm font-semibold text-neza-wine hover:underline">Crear formulario</a></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-between border-t border-neza-line px-5 py-4 text-xs text-neza-muted"><span>Mostrando {{ $formularios->count() }} de {{ $formularios->total() }} resultados</span><div class="flex gap-2">@if ($formularios->onFirstPage())<span class="rounded-md border border-neza-line px-2 py-1 opacity-50">Anterior</span>@else<a href="{{ $formularios->previousPageUrl() }}" class="rounded-md border border-neza-line px-2 py-1 hover:bg-neza-canvas">Anterior</a>@endif @if ($formularios->hasMorePages())<a href="{{ $formularios->nextPageUrl() }}" class="rounded-md border border-neza-line px-2 py-1 hover:bg-neza-canvas">Siguiente</a>@else<span class="rounded-md border border-neza-line px-2 py-1 opacity-50">Siguiente</span>@endif</div></div>
            </section>
            <aside class="space-y-5">
                <section class="rounded-xl border border-neza-line bg-white p-5 shadow-sm"><h2 class="text-base font-bold text-neza-ink">Resumen rápido</h2><dl class="mt-4 divide-y divide-neza-line"><div class="flex items-center justify-between py-3"><dt class="text-sm text-neza-muted">Publicados</dt><dd class="text-lg font-bold text-emerald-700">0</dd></div><div class="flex items-center justify-between py-3"><dt class="text-sm text-neza-muted">Borradores</dt><dd class="text-lg font-bold text-amber-700">0</dd></div><div class="flex items-center justify-between py-3"><dt class="text-sm text-neza-muted">Archivados</dt><dd class="text-lg font-bold text-slate-600">0</dd></div><div class="flex items-center justify-between py-3"><dt class="text-sm text-neza-muted">Asignados a trámites</dt><dd class="text-lg font-bold text-neza-wine">0</dd></div></dl></section>
                <section class="rounded-xl border border-neza-line bg-white p-5 shadow-sm"><div class="flex items-center justify-between gap-3"><h2 class="text-base font-bold text-neza-ink">Actividad reciente</h2><a href="#" class="text-xs font-semibold text-neza-wine hover:underline">Ver todo</a></div><p class="mt-5 rounded-lg border border-dashed border-neza-line px-4 py-6 text-center text-sm text-neza-muted">No hay actividad reciente.</p></section>
                <section class="rounded-xl border border-neza-line bg-white p-5 shadow-sm"><h2 class="text-base font-bold text-neza-ink">Campos más usados</h2><p class="mt-4 text-sm text-neza-muted">Aún no hay campos registrados.</p></section>
            </aside>
        </div>
    </div>
@endsection
