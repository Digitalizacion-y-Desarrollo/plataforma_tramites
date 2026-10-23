@extends('layouts.publico')

@section('title', 'Todos los trámites')

@section('content')
    @php
        $hayFiltros = $filtros['q'] !== '' || count($filtros['modalidad']) > 0 || $filtros['dependencia'] !== '';
        $urlSinBusqueda = route('portal.tramites.index', array_filter([
            'dependencia' => $filtros['dependencia'] ?: null,
            'modalidad' => $filtros['modalidad'] ?: null,
        ]));
        $quitarFiltro = fn (array $cambios) => route('portal.tramites.index', array_filter(array_merge([
            'q' => $filtros['q'] ?: null,
            'dependencia' => $filtros['dependencia'] ?: null,
            'modalidad' => $filtros['modalidad'] ?: null,
        ], $cambios)));
    @endphp

    <section class="border-b border-neza-line bg-white px-4 py-9 sm:px-6 lg:px-[72px]">
        <div class="mx-auto max-w-[1296px]">
            <nav class="text-[13px] text-neza-muted" aria-label="Migas de pan"><a href="{{ route('portal.publico') }}" class="font-bold text-neza-wine">Inicio</a><span class="mx-2">/</span>Trámites</nav>
            <h1 class="mt-3 text-3xl font-bold sm:text-[40px]">Todos los trámites</h1>
            <p class="mt-2 text-[15px] text-neza-muted">Encuentra información, requisitos y opciones para realizar tus trámites municipales.</p>
            <form class="mt-8 flex w-full max-w-[900px] gap-3" action="{{ route('portal.tramites.index') }}" method="GET" role="search">
                <label for="catalogo-busqueda" class="sr-only">Buscar trámite</label>
                <div class="flex h-16 min-w-0 flex-1 items-center gap-2 rounded-2xl border border-neza-line pl-5 pr-2 shadow-sm focus-within:border-neza-wine">
                    <svg class="size-5 shrink-0 text-neza-muted" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.4a6.75 6.75 0 11-13.5 0 6.75 6.75 0 0113.5 0z" /></svg>
                    <input id="catalogo-busqueda" name="q" value="{{ $filtros['q'] }}" class="min-w-0 flex-1 border-0 bg-transparent text-sm outline-none placeholder:text-neza-muted" placeholder="Buscar por nombre del trámite, palabra clave o dependencia">
                    @if ($filtros['q'] !== '')
                        <a href="{{ $urlSinBusqueda }}" aria-label="Borrar el texto de búsqueda" class="grid size-9 shrink-0 place-items-center rounded-full text-neza-muted transition hover:bg-[#f1f3f5] hover:text-neza-wine focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                            <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </a>
                    @endif
                </div>
                <button class="h-16 rounded-2xl bg-neza-wine px-7 text-sm font-bold text-white hover:bg-[#4c0f26] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Buscar</button>
            </form>
        </div>
    </section>

    <section id="resultados" class="bg-[#f5f6f7] px-4 py-10 sm:px-6 lg:px-[72px]">
        <div class="mx-auto max-w-[1296px] lg:grid lg:grid-cols-[260px_minmax(0,1fr)] lg:items-start lg:gap-6">
            <aside aria-labelledby="filtros-titulo" class="rounded-2xl border border-neza-line bg-white p-5 lg:sticky lg:top-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 id="filtros-titulo" class="text-base font-bold">Filtrar resultados</h2>
                        <p class="mt-1 text-xs text-neza-muted">Elige una dependencia o modalidad.</p>
                    </div>
                    @if ($hayFiltros)
                        <a href="{{ route('portal.tramites.index') }}" class="shrink-0 text-xs font-bold text-neza-wine underline-offset-2 hover:underline">Limpiar</a>
                    @endif
                </div>

                @if ($dependencias->isNotEmpty() || $modalidades->isNotEmpty())
                    <form method="GET" action="{{ route('portal.tramites.index') }}" data-envio-automatico class="mt-5 space-y-6" aria-label="Filtrar trámites">
                        @if ($filtros['q'] !== '')
                            <input type="hidden" name="q" value="{{ $filtros['q'] }}">
                        @endif

                        @if ($dependencias->isNotEmpty())
                            <fieldset>
                                <legend class="text-[11px] font-bold uppercase tracking-[0.12em] text-neza-muted">Dependencia</legend>
                                <div class="mt-2 space-y-1">
                                    <label class="flex min-h-11 cursor-pointer items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm text-neza-ink transition hover:bg-neza-canvas has-[:checked]:bg-neza-wine-soft has-[:checked]:font-bold has-[:checked]:text-neza-wine has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-neza-wine/40">
                                        <span class="flex min-w-0 items-center gap-2.5">
                                            <input type="radio" name="dependencia" value="" @checked($filtros['dependencia'] === '') class="size-4 shrink-0 accent-neza-wine">
                                            <span>Todas las dependencias</span>
                                        </span>
                                        <span class="shrink-0 rounded-full bg-white px-2 py-0.5 text-[11px] font-bold text-neza-muted ring-1 ring-neza-line">{{ $totalSinFiltroDependencia }}</span>
                                    </label>
                                    @foreach ($dependencias as $dependencia)
                                        <label class="flex min-h-11 cursor-pointer items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm text-neza-ink transition hover:bg-neza-canvas has-[:checked]:bg-neza-wine-soft has-[:checked]:font-bold has-[:checked]:text-neza-wine has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-neza-wine/40">
                                            <span class="flex min-w-0 items-center gap-2.5">
                                                <input type="radio" name="dependencia" value="{{ $dependencia['clave'] }}" @checked($filtros['dependencia'] === $dependencia['clave']) class="size-4 shrink-0 accent-neza-wine">
                                                <span>{{ $dependencia['nombre'] }}</span>
                                            </span>
                                            <span @class([
                                                'shrink-0 rounded-full px-2 py-0.5 text-[11px] font-bold ring-1',
                                                'bg-white text-neza-muted ring-neza-line' => $dependencia['total'] > 0,
                                                'bg-[#f4f5f6] text-neza-muted/70 ring-neza-line' => $dependencia['total'] === 0,
                                            ])>{{ $dependencia['total'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endif

                        @if ($modalidades->isNotEmpty())
                            <fieldset>
                                <legend class="text-[11px] font-bold uppercase tracking-[0.12em] text-neza-muted">Modalidad</legend>
                                <div class="mt-2 space-y-1">
                                    @foreach ($modalidades as $opcion)
                                        <label class="flex min-h-10 cursor-pointer items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-neza-ink transition hover:bg-neza-canvas has-[:checked]:bg-neza-wine-soft has-[:checked]:font-bold has-[:checked]:text-neza-wine has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-neza-wine/40">
                                            <input type="checkbox" name="modalidad[]" value="{{ $opcion }}" @checked(in_array($opcion, $filtros['modalidad'], true)) class="size-4 shrink-0 rounded accent-neza-wine">
                                            <span>{{ $opcion }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endif

                        <noscript>
                            <button type="submit" class="w-full rounded-xl bg-neza-wine px-4 py-2.5 text-sm font-bold text-white hover:bg-[#4c0f26] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Aplicar filtros</button>
                        </noscript>
                    </form>
                @endif
            </aside>

            <div class="mt-6 min-w-0 lg:mt-0">
                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                    <p class="text-sm text-neza-muted" role="status" aria-live="polite">
                        @if ($hayFiltros)
                            Se encontraron <strong class="text-neza-ink">{{ $total }}</strong> {{ $total === 1 ? 'trámite' : 'trámites' }} para tu búsqueda.
                        @else
                            <strong class="text-neza-ink">{{ $total }}</strong> {{ $total === 1 ? 'trámite disponible' : 'trámites disponibles' }}@if ($dependencias->isNotEmpty()) en {{ $dependencias->count() }} {{ $dependencias->count() === 1 ? 'dependencia' : 'dependencias' }}@endif.
                        @endif
                    </p>
                    <a href="{{ route('portal.publico') }}#ayuda" class="text-xs font-bold text-neza-wine underline-offset-2 hover:underline">¿Necesitas ayuda?</a>
                </div>

                @if ($hayFiltros)
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-bold uppercase tracking-wide text-neza-muted">Filtros activos</span>
                        @if ($filtros['q'] !== '')
                            <span class="inline-flex items-center gap-1 rounded-full border border-neza-line bg-white py-1 pl-3 pr-1 text-xs font-semibold text-neza-ink">
                                Búsqueda: "{{ $filtros['q'] }}"
                                <a href="{{ $quitarFiltro(['q' => null]) }}" aria-label="Quitar el filtro de búsqueda" class="grid size-6 place-items-center rounded-full text-neza-muted transition hover:bg-neza-wine-soft hover:text-neza-wine focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-neza-wine">
                                    <svg class="size-3.5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </a>
                            </span>
                        @endif
                        @if ($filtros['dependencia'] !== '')
                            @php($nombreDependenciaSeleccionada = $dependencias->firstWhere('clave', $filtros['dependencia'])['nombre'] ?? 'Dependencia')
                            <span class="inline-flex items-center gap-1 rounded-full border border-neza-line bg-white py-1 pl-3 pr-1 text-xs font-semibold text-neza-ink">
                                Dependencia: {{ $nombreDependenciaSeleccionada }}
                                <a href="{{ $quitarFiltro(['dependencia' => null]) }}" aria-label="Quitar el filtro de dependencia" class="grid size-6 place-items-center rounded-full text-neza-muted transition hover:bg-neza-wine-soft hover:text-neza-wine focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-neza-wine">
                                    <svg class="size-3.5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </a>
                            </span>
                        @endif
                        @foreach ($filtros['modalidad'] as $modalidad)
                            <span class="inline-flex items-center gap-1 rounded-full border border-neza-line bg-white py-1 pl-3 pr-1 text-xs font-semibold text-neza-ink">
                                Modalidad: {{ $modalidad }}
                                <a href="{{ $quitarFiltro(['modalidad' => array_values(array_diff($filtros['modalidad'], [$modalidad]))]) }}" aria-label="Quitar el filtro de modalidad {{ $modalidad }}" class="grid size-6 place-items-center rounded-full text-neza-muted transition hover:bg-neza-wine-soft hover:text-neza-wine focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-neza-wine">
                                    <svg class="size-3.5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </a>
                            </span>
                        @endforeach
                    </div>
                @endif

                @if ($grupos->isNotEmpty())
                    <div class="mt-6 space-y-8">
                        @foreach ($grupos as $grupo)
                            <section aria-labelledby="dependencia-{{ $loop->index }}">
                                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-neza-line pb-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-white text-neza-wine ring-1 ring-neza-line">
                                            <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 10.5h15M5.25 10.5v8.25m4.5-8.25v8.25m4.5-8.25v8.25m4.5-8.25v8.25M12 3l8.25 4.5H3.75L12 3z" /></svg>
                                        </span>
                                        <div class="min-w-0">
                                            <h2 id="dependencia-{{ $loop->index }}" class="text-lg font-bold leading-tight text-neza-ink">{{ $grupo['nombre'] }}</h2>
                                            <p class="text-xs text-neza-muted">Dependencia responsable</p>
                                        </div>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-white px-3 py-1 text-xs font-bold text-neza-muted ring-1 ring-neza-line">{{ $grupo['tramites']->count() }} {{ $grupo['tramites']->count() === 1 ? 'trámite' : 'trámites' }}</span>
                                </div>
                                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                                    @foreach ($grupo['tramites'] as $tramite)
                                        @php($modalidadEtiqueta = collect($tramite->modalidades ?? [])->unique()->values()->count() > 1 ? 'Mixto' : (collect($tramite->modalidades ?? [])->first() === 'EN_LINEA' ? 'En línea' : 'Presencial'))
                                        @include('partials.publico.tramite-card', ['tramite' => [
                                            'nombre' => $tramite->nombre,
                                            'categoria' => 'Trámite municipal',
                                            'descripcion' => $tramite->descripcion ?: 'Consulta la información de este trámite.',
                                            'dependencia' => $grupo['nombre'],
                                            'modalidad' => $modalidadEtiqueta,
                                            'costo' => $tramite->costo ?: 'Costo por definir',
                                            'tiempo' => $tramite->tiempo_estimado ?: 'Tiempo por definir',
                                            'url' => route('portal.tramites.detalle', $tramite),
                                        ]])
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                @else
                    <div class="mt-6 rounded-2xl border border-dashed border-neza-line bg-white p-10 text-center">
                        <span class="mx-auto grid size-12 place-items-center rounded-xl bg-neza-wine-soft text-neza-wine"><svg class="size-6" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.4a6.75 6.75 0 11-13.5 0 6.75 6.75 0 0113.5 0z" /></svg></span>
                        <h2 class="mt-4 text-lg font-bold">No encontramos trámites con esa búsqueda</h2>
                        <p class="mx-auto mt-2 max-w-md text-sm text-neza-muted">Prueba con otras palabras, elige otra dependencia o revisa el catálogo completo. También puedes recibir orientación con los canales de ayuda del municipio.</p>
                        <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                            @if ($hayFiltros)
                                <a href="{{ route('portal.tramites.index') }}" class="inline-flex min-h-11 items-center rounded-xl bg-neza-wine px-5 text-sm font-bold text-white hover:bg-[#4c0f26] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Quitar filtros</a>
                            @endif
                            <a href="{{ route('portal.publico') }}#ayuda" class="inline-flex min-h-11 items-center rounded-xl border border-neza-wine px-5 text-sm font-bold text-neza-wine hover:bg-neza-wine-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Recibir orientación</a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    @if ($total > 0)
        <section class="bg-white px-4 py-12 sm:px-6 lg:px-[72px]">
            <div class="mx-auto flex max-w-[1296px] flex-col gap-6 rounded-2xl bg-[#340c1b] px-7 py-9 text-white lg:flex-row lg:items-center lg:justify-between lg:px-10">
                <div><h2 class="text-2xl font-bold">¿No encuentras el trámite que necesitas?</h2><p class="mt-2 max-w-2xl text-sm text-white/70">Nuestro equipo de atención puede orientarte para identificar el servicio, la dependencia y los requisitos correctos.</p></div>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    @if (filled($canalesAyuda?->telefono_contacto))
                        <div><p class="text-[10px] uppercase text-[#be985c]">Atención telefónica</p><a href="tel:{{ preg_replace('/\D+/', '', $canalesAyuda->telefono_contacto) }}" class="text-xl font-bold hover:underline">{{ $canalesAyuda->telefono_contacto }}</a></div>
                    @endif
                    <a href="{{ route('portal.publico') }}#ayuda" class="rounded-xl bg-white px-5 py-3 text-center text-sm font-bold text-neza-wine hover:bg-[#fdfbf8]">Recibir orientación</a>
                </div>
            </div>
        </section>
    @endif
@endsection
