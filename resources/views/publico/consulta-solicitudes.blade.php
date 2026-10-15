@extends('layouts.publico')

@section('content')
    <section class="bg-neza-canvas px-4 py-12 sm:px-6 lg:px-[72px]">
        <div class="mx-auto max-w-4xl">
            <a href="{{ route('portal.publico') }}#consulta" class="text-sm font-semibold text-neza-wine hover:underline">Volver a la consulta</a>
            <h1 class="mt-4 text-3xl font-bold text-neza-ink">Tus solicitudes</h1>
            <p class="mt-2 text-sm text-neza-muted">Esta sesión solo muestra los expedientes asociados a tu CURP.</p>
            <div class="mt-7 space-y-4">
                @forelse ($solicitudes as $solicitud)
                    <article class="rounded-xl border border-neza-line bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div><p class="font-mono text-sm font-semibold text-neza-wine">{{ $solicitud->folio }}</p><h2 class="mt-1 text-lg font-bold text-neza-ink">{{ $solicitud->tramite->nombre }}</h2><p class="mt-1 text-sm text-neza-muted">Estado: {{ $solicitud->estado->nombre }}</p></div>
                            @if ($solicitud->estado->clave === 'APROBADA' && $solicitud->documentoEmitido)
                                @if ($solicitud->documentoEmitido->fecha_expiracion !== null && now()->greaterThan($solicitud->documentoEmitido->fecha_expiracion))
                                    <p class="text-sm font-semibold text-rose-700">Descarga vencida el {{ $solicitud->documentoEmitido->fecha_expiracion->translatedFormat('d \d\e F \d\e Y') }}</p>
                                @else
                                    <div class="flex flex-col items-stretch gap-2 sm:items-end">
                                        <a href="{{ route('consulta.documentos.descargar', $solicitud->documentoEmitido) }}" class="inline-flex items-center justify-center rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Descargar documento final</a>
                                        @if ($solicitud->documentoEmitido->fecha_expiracion !== null)
                                            <p class="text-xs text-neza-muted">Disponible hasta el {{ $solicitud->documentoEmitido->fecha_expiracion->translatedFormat('d \d\e F \d\e Y') }}</p>
                                        @endif
                                    </div>
                                @endif
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="rounded-xl border border-neza-line bg-white p-6 text-sm text-neza-muted">No hay solicitudes para mostrar.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
