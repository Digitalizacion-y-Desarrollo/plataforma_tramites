@extends('layouts.publico')

@section('title', 'Todos los trámites')

@section('content')
    <section class="border-b border-neza-line bg-white px-4 py-9 sm:px-6 lg:px-[72px]">
        <div class="mx-auto max-w-[1296px]">
            <nav class="text-[13px] text-neza-muted" aria-label="Migas de pan"><a href="{{ route('portal.publico') }}" class="font-bold text-neza-wine">Inicio</a><span class="mx-2">/</span>Trámites</nav>
            <h1 class="mt-3 text-3xl font-bold sm:text-[40px]">Todos los trámites</h1>
            <p class="mt-2 text-[15px] text-neza-muted">Encuentra información, requisitos y opciones para realizar tus trámites municipales.</p>
            <form class="mt-8 flex w-full max-w-[900px] gap-3" action="{{ route('portal.tramites.index') }}" method="GET" role="search">
                <label for="catalogo-busqueda" class="sr-only">Buscar trámite</label>
                <div class="flex h-16 min-w-0 flex-1 items-center gap-3 rounded-2xl border border-neza-line px-5 shadow-sm focus-within:border-neza-wine"><svg class="size-5 shrink-0 text-neza-muted" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.4a6.75 6.75 0 11-13.5 0 6.75 6.75 0 0113.5 0z" /></svg><input id="catalogo-busqueda" name="q" value="{{ $filtros['q'] }}" class="min-w-0 flex-1 border-0 bg-transparent text-sm outline-none placeholder:text-neza-muted" placeholder="Buscar por nombre del trámite o palabra clave"></div>
                <button class="h-16 rounded-2xl bg-neza-wine px-7 text-sm font-bold text-white hover:bg-[#4c0f26] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Buscar</button>
            </form>
        </div>
    </section>

    <section id="resultados" class="bg-[#f5f6f7] px-4 py-10 sm:px-6 lg:px-[72px]">
        <div class="mx-auto max-w-[1296px]">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-neza-muted" role="status" aria-live="polite">
                    @if ($filtros['q'] !== '' || count($filtros['modalidad']) > 0)
                        Se encontraron <strong class="text-neza-ink">{{ $total }}</strong> {{ $total === 1 ? 'trámite' : 'trámites' }} para tu búsqueda.
                    @else
                        <strong class="text-neza-ink">{{ $total }}</strong> {{ $total === 1 ? 'trámite disponible' : 'trámites disponibles' }}
                    @endif
                </p>
                @if ($filtros['q'] !== '' || count($filtros['modalidad']) > 0)
                    <a href="{{ route('portal.tramites.index') }}" class="inline-flex shrink-0 items-center gap-2 text-sm font-bold text-neza-wine underline-offset-2 hover:underline">Limpiar búsqueda<svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></a>
                @endif
            </div>

            @if ($modalidades->isNotEmpty())
                <form method="GET" action="{{ route('portal.tramites.index') }}" data-envio-automatico class="mt-6 rounded-2xl border border-neza-line bg-white p-4" aria-label="Filtrar por modalidad">
                    @if ($filtros['q'] !== '')
                        <input type="hidden" name="q" value="{{ $filtros['q'] }}">
                    @endif
                    <fieldset>
                        <legend class="text-sm font-bold">Modalidad</legend>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($modalidades as $opcion)
                                <label class="inline-flex cursor-pointer items-center gap-2.5 rounded-full border px-4 py-2 text-xs font-bold transition {{ in_array($opcion, $filtros['modalidad'], true) ? 'border-neza-wine bg-neza-wine text-white' : 'border-neza-line bg-[#f4f5f6] text-neza-ink hover:border-neza-wine' }} has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-neza-wine/50 has-[:focus-visible]:ring-offset-1">
                                    <input type="checkbox" name="modalidad[]" value="{{ $opcion }}" @checked(in_array($opcion, $filtros['modalidad'], true)) class="size-4 rounded border-neza-line bg-white accent-neza-wine checked:accent-white">
                                    {{ $opcion }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                </form>
            @endif

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                @forelse ($tramites as $tramite)
                    @php($modalidadEtiqueta = collect($tramite->modalidades ?? [])->unique()->values()->count() > 1 ? 'Mixto' : (collect($tramite->modalidades ?? [])->first() === 'EN_LINEA' ? 'En línea' : 'Presencial'))
                    @include('partials.publico.tramite-card', ['tramite' => [
                        'nombre' => $tramite->nombre,
                        'categoria' => 'Trámite municipal',
                        'descripcion' => $tramite->descripcion ?: 'Consulta la información de este trámite.',
                        'dependencia' => 'Municipio de Nezahualcóyotl',
                        'modalidad' => $modalidadEtiqueta,
                        'costo' => $tramite->costo ?: 'Costo por definir',
                        'tiempo' => $tramite->tiempo_estimado ?: 'Tiempo por definir',
                        'url' => route('portal.tramites.detalle', $tramite),
                    ]])
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-neza-line bg-white p-10 text-center">
                        <span class="mx-auto grid size-12 place-items-center rounded-xl bg-neza-wine-soft text-neza-wine"><svg class="size-6" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.4a6.75 6.75 0 11-13.5 0 6.75 6.75 0 0113.5 0z" /></svg></span>
                        <h2 class="mt-4 text-lg font-bold">No encontramos trámites con esa búsqueda</h2>
                        <p class="mx-auto mt-2 max-w-md text-sm text-neza-muted">Prueba con otras palabras o revisa el catálogo completo. También puedes recibir orientación con los canales de ayuda del municipio.</p>
                        <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                            <a href="{{ route('portal.tramites.index') }}" class="inline-flex min-h-11 items-center rounded-xl bg-neza-wine px-5 text-sm font-bold text-white hover:bg-[#4c0f26] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Ver todos los trámites</a>
                            <a href="{{ route('portal.publico') }}#ayuda" class="inline-flex min-h-11 items-center rounded-xl border border-neza-wine px-5 text-sm font-bold text-neza-wine hover:bg-neza-wine-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Recibir orientación</a>
                        </div>
                    </div>
                @endforelse
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
