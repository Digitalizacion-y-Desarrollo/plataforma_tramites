@extends('layouts.publico')

@section('title', 'Todos los trámites')

@section('content')
    @php
        $tramites = [
            ['nombre' => 'Pago de predial', 'categoria' => 'Vivienda y propiedad', 'descripcion' => 'Consulta tu saldo y realiza el pago del impuesto predial de tu inmueble.', 'dependencia' => 'Tesorería Municipal', 'modalidad' => 'En línea', 'costo' => 'Con costo', 'tiempo' => '5–10 min', 'url' => route('portal.tramites.predial')],
            ['nombre' => 'Licencia de funcionamiento', 'categoria' => 'Negocios y comercio', 'descripcion' => 'Solicita o renueva la licencia para operar un establecimiento comercial.', 'dependencia' => 'Desarrollo Económico', 'modalidad' => 'Mixto', 'costo' => 'Con costo', 'tiempo' => '3–5 días', 'url' => '#'],
            ['nombre' => 'Constancia de residencia', 'categoria' => 'Identidad y documentos', 'descripcion' => 'Acredita tu domicilio actual dentro del municipio de Nezahualcóyotl.', 'dependencia' => 'Secretaría del Ayuntamiento', 'modalidad' => 'Presencial', 'costo' => 'Gratuito', 'tiempo' => '1 día', 'url' => '#'],
            ['nombre' => 'Permiso de uso de suelo', 'categoria' => 'Vivienda y propiedad', 'descripcion' => 'Obtén la autorización municipal para el uso previsto de un predio.', 'dependencia' => 'Desarrollo Urbano', 'modalidad' => 'Mixto', 'costo' => 'Con costo', 'tiempo' => '7–10 días', 'url' => '#'],
            ['nombre' => 'Licencia de construcción', 'categoria' => 'Vivienda y propiedad', 'descripcion' => 'Tramita el permiso para obra nueva, ampliación o modificación de un inmueble.', 'dependencia' => 'Desarrollo Urbano', 'modalidad' => 'Presencial', 'costo' => 'Con costo', 'tiempo' => '10–15 días', 'url' => '#'],
            ['nombre' => 'Alineamiento y número oficial', 'categoria' => 'Vivienda y propiedad', 'descripcion' => 'Solicita la delimitación oficial del predio y la asignación de número.', 'dependencia' => 'Desarrollo Urbano', 'modalidad' => 'Mixto', 'costo' => 'Con costo', 'tiempo' => '5–8 días', 'url' => '#'],
            ['nombre' => 'Constancia de no adeudo', 'categoria' => 'Vivienda y propiedad', 'descripcion' => 'Acredita que tu cuenta predial se encuentra al corriente de pagos.', 'dependencia' => 'Tesorería Municipal', 'modalidad' => 'En línea', 'costo' => 'Gratuito', 'tiempo' => '10 min', 'url' => '#'],
            ['nombre' => 'Registro de establecimiento', 'categoria' => 'Negocios y comercio', 'descripcion' => 'Incorpora tu negocio al padrón municipal de unidades económicas.', 'dependencia' => 'Desarrollo Económico', 'modalidad' => 'En línea', 'costo' => 'Gratuito', 'tiempo' => '2 días', 'url' => '#'],
            ['nombre' => 'Permiso para evento', 'categoria' => 'Servicios urbanos', 'descripcion' => 'Gestiona la autorización para realizar un evento en espacio público.', 'dependencia' => 'Gobierno Municipal', 'modalidad' => 'Presencial', 'costo' => 'Con costo', 'tiempo' => '5 días', 'url' => '#'],
            ['nombre' => 'Copia certificada de acta', 'categoria' => 'Identidad y documentos', 'descripcion' => 'Solicita una copia certificada de acta de nacimiento, matrimonio o defunción.', 'dependencia' => 'Registro Civil', 'modalidad' => 'Mixto', 'costo' => 'Con costo', 'tiempo' => '1 día', 'url' => '#'],
            ['nombre' => 'Reporte de servicios públicos', 'categoria' => 'Servicios urbanos', 'descripcion' => 'Reporta luminarias, baches, recolección o mantenimiento urbano.', 'dependencia' => 'Servicios Públicos', 'modalidad' => 'En línea', 'costo' => 'Gratuito', 'tiempo' => '3–7 días', 'url' => '#'],
            ['nombre' => 'Dictamen de Protección Civil', 'categoria' => 'Protección civil', 'descripcion' => 'Solicita la evaluación de medidas de seguridad para tu establecimiento.', 'dependencia' => 'Protección Civil', 'modalidad' => 'Mixto', 'costo' => 'Con costo', 'tiempo' => '10 días', 'url' => '#'],
        ];
        $categorias = ['Todos', 'Vivienda y propiedad', 'Negocios y comercio', 'Identidad y documentos', 'Servicios urbanos', 'Movilidad', 'Desarrollo social', 'Protección civil', 'Medio ambiente'];
    @endphp

    <section class="border-b border-neza-line bg-white px-4 py-9 sm:px-6 lg:px-[72px]">
        <div class="mx-auto max-w-[1296px]">
            <nav class="text-[13px] text-neza-muted" aria-label="Migas de pan"><a href="{{ route('portal.publico') }}" class="font-bold text-neza-wine">Inicio</a><span class="mx-2">/</span>Trámites</nav>
            <h1 class="mt-3 text-3xl font-bold sm:text-[40px]">Todos los trámites</h1>
            <p class="mt-2 text-[15px] text-neza-muted">Encuentra información, requisitos y opciones para realizar tus trámites municipales.</p>
            <div class="mt-8 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <form class="flex w-full max-w-[900px] gap-3" action="{{ route('portal.tramites.index') }}" method="GET" role="search">
                    <label for="catalogo-busqueda" class="sr-only">Buscar trámite</label>
                    <div class="flex h-16 min-w-0 flex-1 items-center gap-3 rounded-2xl border border-neza-line px-5 shadow-sm focus-within:border-neza-wine"><svg class="size-5 shrink-0 text-neza-muted" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.4a6.75 6.75 0 11-13.5 0 6.75 6.75 0 0113.5 0z" /></svg><input id="catalogo-busqueda" name="q" value="{{ request('q') }}" class="min-w-0 flex-1 border-0 bg-transparent text-sm outline-none placeholder:text-neza-muted" placeholder="Buscar por trámite, servicio, dependencia o palabra clave"></div>
                    <button class="h-16 rounded-2xl bg-neza-wine px-7 text-sm font-bold text-white hover:bg-[#4c0f26]">Buscar</button>
                </form>
                <a href="{{ route('portal.publico') }}#consulta" class="inline-flex shrink-0 items-center gap-2 text-sm font-bold text-neza-wine">Consultar estado de un trámite <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg></a>
            </div>
            <div class="mt-8 flex gap-2 overflow-x-auto border-t border-neza-line pt-7 pb-1">
                @foreach ($categorias as $categoria)
                    <a href="#resultados" class="shrink-0 rounded-full border px-4 py-3 text-xs font-bold {{ $loop->first ? 'border-neza-wine bg-neza-wine text-white' : 'border-neza-line bg-[#f4f5f6] text-neza-ink hover:border-neza-wine' }}">{{ $categoria }}</a>
                @endforeach
            </div>
        </div>
    </section>

    <section id="resultados" class="bg-[#f5f6f7] px-4 py-10 sm:px-6 lg:px-[72px]">
        <div class="mx-auto grid max-w-[1296px] gap-7 lg:grid-cols-[276px_1fr]">
            <aside class="h-fit rounded-2xl border border-neza-line bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between"><h2 class="flex items-center gap-2 font-bold"><svg class="size-5 text-neza-wine" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9m-9 6h9m-9 6h9M3.75 6h.008v.008H3.75V6zm0 6h.008v.008H3.75V12zm0 6h.008v.008H3.75V18z" /></svg>Filtros</h2><button class="text-xs font-bold text-neza-wine">Limpiar filtros</button></div>
                <div class="mt-4 rounded-xl bg-neza-wine-soft p-3 text-xs"><strong class="block">1 filtro aplicado</strong><span class="mt-1 flex items-center justify-between">Modalidad: En línea <button type="button" class="font-bold text-neza-wine">Quitar</button></span></div>
                @foreach (['Modalidad' => ['En línea', 'Presencial', 'Mixto'], 'Dependencia' => ['Tesorería Municipal', 'Desarrollo Urbano', 'Desarrollo Económico', 'Servicios Públicos', 'Protección Civil'], 'Costo' => ['Gratuito', 'Con costo'], 'Tiempo estimado' => ['Menos de 30 minutos', '1 a 3 días', '4 a 10 días', 'Más de 10 días']] as $grupo => $opciones)
                    <fieldset class="mt-5 border-t border-neza-line pt-5 first:border-0 first:pt-0"><legend class="mb-3 text-sm font-bold">{{ $grupo }}</legend><div class="space-y-2.5">@foreach ($opciones as $opcion)<label class="flex items-center gap-2 text-xs text-neza-muted"><input type="checkbox" class="size-[18px] rounded border-neza-line accent-neza-wine" @checked($grupo === 'Modalidad' && $loop->first)> {{ $opcion }}</label>@endforeach</div></fieldset>
                @endforeach
                <button class="mt-5 h-11 w-full rounded-xl border border-neza-wine text-xs font-bold text-neza-wine hover:bg-neza-wine-soft">Limpiar filtros</button>
            </aside>

            <div>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-xl font-bold">24 trámites disponibles</h2><p class="text-xs text-neza-muted">Información municipal presentada como propuesta.</p></div><label class="flex items-center gap-2 text-xs text-neza-muted">Ordenar: <select class="h-10 rounded-xl border border-neza-line bg-white px-3 font-bold text-neza-ink"><option>Más consultados</option><option>Nombre</option></select></label></div>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    @foreach ($tramites as $tramite)
                        @include('partials.publico.tramite-card', ['tramite' => $tramite])
                    @endforeach
                </div>
                <div class="mt-8 flex flex-col gap-4 text-xs text-neza-muted sm:flex-row sm:items-center sm:justify-between"><p>Mostrando 1–12 de 24 trámites</p><nav class="flex gap-2" aria-label="Paginación"><a href="#" class="rounded-lg border border-neza-line bg-white px-4 py-2">Anterior</a><a href="#" class="rounded-lg bg-neza-wine px-4 py-2 font-bold text-white">1</a><a href="#" class="rounded-lg border border-neza-line bg-white px-4 py-2">2</a><a href="#" class="rounded-lg border border-neza-line bg-white px-4 py-2 font-bold text-neza-wine">Siguiente</a></nav></div>
            </div>
        </div>
    </section>

    <section class="bg-white px-4 py-12 sm:px-6 lg:px-[72px]">
        <div class="mx-auto flex max-w-[1296px] flex-col gap-6 rounded-2xl bg-[#340c1b] px-7 py-9 text-white lg:flex-row lg:items-center lg:justify-between lg:px-10">
            <div><h2 class="text-2xl font-bold">¿No encuentras el trámite que necesitas?</h2><p class="mt-2 max-w-2xl text-sm text-white/70">Nuestro equipo de atención puede orientarte para identificar el servicio, la dependencia y los requisitos correctos.</p></div><div class="flex items-center gap-6"><div><p class="text-[10px] uppercase text-[#be985c]">Atención telefónica</p><a href="tel:5557169070" class="text-xl font-bold">55 5716 9070</a></div><a href="{{ route('portal.publico') }}#ayuda" class="rounded-xl bg-white px-5 py-3 text-sm font-bold text-neza-wine">Recibir orientación</a></div>
        </div>
    </section>
@endsection
