@extends('layouts.publico')

@section('title', 'Mi portal de trámites')

@section('content')
    <section class="bg-neza-canvas px-4 py-8 sm:px-6 sm:py-10 lg:px-[72px]">
        <div class="mx-auto max-w-6xl">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-neza-wine">Seguimiento ciudadano</p>
                    <h1 class="mt-2 text-3xl font-bold text-neza-ink">Hola, {{ $nombre }}</h1>
                    <p class="mt-2 text-sm text-neza-muted">Aquí puedes consultar tus solicitudes y sus avances. Tu acceso es temporal.</p>
                </div>
                <form action="{{ route('consulta.cerrar') }}" method="POST">@csrf<button type="submit" class="min-h-11 rounded-xl border border-neza-wine px-4 py-2.5 text-sm font-bold text-neza-wine hover:bg-neza-wine-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Cerrar consulta</button></form>
            </div>
            <section aria-label="Datos de consulta" class="mt-7 grid gap-4 rounded-2xl border border-neza-line bg-white p-5 shadow-sm sm:grid-cols-3 sm:p-6">
                <div><p class="text-xs font-semibold text-neza-muted">Nombre</p><p class="mt-1 font-bold text-neza-ink">{{ $nombre }}</p></div>
                <div><p class="text-xs font-semibold text-neza-muted">CURP</p><p class="mt-1 break-all font-bold text-neza-ink">@php($curp = $acceso['curp']){{ \Illuminate\Support\Str::of($curp)->substr(0, 4)->append(str_repeat('·', max(0, strlen($curp) - 5)))->append(\Illuminate\Support\Str::of($curp)->substr(-1)) }}</p></div>
                <div><p class="text-xs font-semibold text-neza-muted">Correo de consulta</p><p class="mt-1 break-all font-bold text-neza-ink">{{ \Illuminate\Support\Str::mask($acceso['email'], '*', 2, max(0, strpos($acceso['email'], '@') - 2)) }}</p></div>
            </section>
            <section aria-labelledby="historial-titulo" class="mt-9">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div><h2 id="historial-titulo" class="text-xl font-bold text-neza-ink">Historial de trámites</h2><p class="mt-1 text-sm text-neza-muted">Solo se muestran las solicitudes asociadas a tu CURP y este correo.</p></div>
                    <p class="text-sm font-semibold text-neza-muted">{{ $total }} {{ $total === 1 ? 'solicitud' : 'solicitudes' }} en total</p>
                </div>
                <form method="GET" action="{{ route('consulta.solicitudes') }}" role="search" class="mt-5 grid gap-3 rounded-2xl border border-neza-line bg-white p-4 sm:grid-cols-[minmax(0,1fr)_minmax(180px,240px)_auto] sm:items-end">
                    <div><label for="buscar-solicitud" class="block text-xs font-bold text-neza-ink">Buscar por folio o trámite</label><input id="buscar-solicitud" name="q" type="search" maxlength="100" value="{{ $filtros['q'] }}" placeholder="Ej. SOL-2026 o Constancia" class="mt-2 h-11 w-full rounded-xl border border-neza-line px-3 text-sm outline-none focus:border-neza-wine focus:ring-2 focus:ring-neza-wine/15">@error('q')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    <div><label for="filtrar-estado" class="block text-xs font-bold text-neza-ink">Estado</label><select id="filtrar-estado" name="estado" class="mt-2 h-11 w-full rounded-xl border border-neza-line bg-white px-3 text-sm outline-none focus:border-neza-wine focus:ring-2 focus:ring-neza-wine/15"><option value="">Todos los estados</option>@foreach ($estados as $estado)<option value="{{ $estado->clave }}" @selected(($filtros['estado'] ?? '') === $estado->clave)>{{ $estado->nombre }}</option>@endforeach</select>@error('estado')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    <button type="submit" class="min-h-11 rounded-xl bg-neza-wine px-5 text-sm font-bold text-white hover:bg-neza-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Aplicar filtros</button>
                </form>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-neza-muted"><p role="status">{{ $solicitudes->total() }} {{ $solicitudes->total() === 1 ? 'resultado' : 'resultados' }}</p>@if ($filtros['q'] !== '' || filled($filtros['estado'] ?? null))<a href="{{ route('consulta.solicitudes') }}" class="font-bold text-neza-wine underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Limpiar filtros</a>@endif</div>
                @if ($solicitudes->isEmpty())
                    <div class="mt-4 rounded-2xl border border-dashed border-neza-line bg-white px-6 py-12 text-center">
                        <span class="mx-auto grid size-12 place-items-center rounded-xl bg-neza-wine-soft text-neza-wine"><svg class="size-6" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg></span>
                        <h3 class="mt-4 font-bold text-neza-ink">{{ $total === 0 ? 'Aún no hay solicitudes para mostrar' : 'No encontramos solicitudes con esos filtros' }}</h3>
                        <p class="mx-auto mt-2 max-w-md text-sm text-neza-muted">{{ $total === 0 ? 'Verifica que hayas usado el mismo correo con el que enviaste tu solicitud.' : 'Prueba con otro folio, nombre de trámite o estado, o revisa el catálogo completo de trámites.' }}</p>
                        <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                            @if ($total === 0)
                                <form action="{{ route('consulta.cerrar') }}" method="POST">@ csrf<button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-neza-wine px-5 text-sm font-bold text-white hover:bg-neza-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Iniciar otra consulta</button></form>
                                <a href="{{ route('portal.tramites.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-neza-wine px-5 text-sm font-bold text-neza-wine hover:bg-neza-wine-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Explorar trámites</a>
                            @else
                                <a href="{{ route('consulta.solicitudes') }}" class="inline-flex min-h-11 items-center rounded-xl bg-neza-wine px-5 text-sm font-bold text-white hover:bg-neza-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Limpiar filtros</a>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="mt-4 space-y-3 md:hidden">
                        @foreach ($solicitudes as $solicitud)
                            <article class="rounded-2xl border border-neza-line bg-white p-5 shadow-sm"><div class="flex flex-wrap items-start justify-between gap-2"><p class="text-sm font-bold text-neza-wine">{{ $solicitud->folio }}</p>@include('partials.publico.solicitud-estado', ['estado' => $solicitud->estado])</div><h3 class="mt-3 font-bold text-neza-ink">{{ $solicitud->tramite?->nombre ?? 'Trámite no disponible' }}</h3><p class="mt-2 text-xs text-neza-muted">Enviada el {{ $solicitud->fecha_envio?->format('d/m/Y') ?? 'Sin fecha' }}</p><a href="{{ route('consulta.solicitudes.detalle', $solicitud) }}" class="mt-4 inline-flex min-h-11 items-center font-bold text-neza-wine underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Ver seguimiento<span class="sr-only"> de {{ $solicitud->folio }}</span></a></article>
                        @endforeach
                    </div>
                    <div class="mt-4 hidden overflow-hidden rounded-2xl border border-neza-line bg-white shadow-sm md:block"><table class="w-full text-left text-sm"><thead class="bg-neza-wine-soft text-neza-ink"><tr><th scope="col" class="px-5 py-4">Folio</th><th scope="col" class="px-5 py-4">Trámite</th><th scope="col" class="px-5 py-4">Fecha de envío</th><th scope="col" class="px-5 py-4">Estado</th><th scope="col" class="px-5 py-4">Acción</th></tr></thead><tbody class="divide-y divide-neza-line">@foreach ($solicitudes as $solicitud)<tr><td class="px-5 py-4 font-semibold text-neza-wine">{{ $solicitud->folio }}</td><td class="px-5 py-4 text-neza-ink">{{ $solicitud->tramite?->nombre ?? 'Trámite no disponible' }}</td><td class="px-5 py-4 text-neza-muted">{{ $solicitud->fecha_envio?->format('d/m/Y') ?? 'Sin fecha' }}</td><td class="px-5 py-4">@include('partials.publico.solicitud-estado', ['estado' => $solicitud->estado])</td><td class="px-5 py-4"><a href="{{ route('consulta.solicitudes.detalle', $solicitud) }}" class="font-bold text-neza-wine underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Ver seguimiento<span class="sr-only"> de {{ $solicitud->folio }}</span></a></td></tr>@endforeach</tbody></table></div>
                    <div class="mt-5">{{ $solicitudes->links() }}</div>
                @endif
            </section>
        </div>
    </section>
@endsection
