@extends('layouts.publico')

@section('title', 'Detalle de mi trámite')

@section('content')
    <section class="bg-neza-canvas px-4 py-10 sm:px-6 lg:px-[72px]">
        <div class="mx-auto max-w-4xl">
            <a href="{{ route('consulta.solicitudes') }}" class="text-sm font-bold text-neza-wine hover:underline">Volver a mis trámites</a>
            <div class="mt-5 rounded-2xl border border-neza-line bg-white p-6 shadow-sm">
                <p class="text-sm font-bold text-neza-wine">{{ $solicitud->folio }}</p><h1 class="mt-2 text-2xl font-bold text-neza-ink">{{ $solicitud->tramite->nombre }}</h1>
                @php
                    [$tituloEstado, $siguientePaso] = match ($solicitud->estado?->clave) {
                        'BORRADOR' => ['Solicitud en borrador', 'Este expediente aún no se ha enviado. Revisa la información de tu solicitud.'],
                        'ENVIADA' => ['Solicitud recibida', 'Estamos revisando tu expediente. Te avisaremos por correo cuando haya un cambio.'],
                        'CORRECCION_SOLICITADA' => ['Se requiere una corrección', 'Revisa las observaciones visibles y el correo recibido para conocer las indicaciones.'],
                        'APROBADA' => ['Solicitud aprobada', 'Si hay un documento final disponible, puedes descargarlo aquí.'],
                        'RECHAZADA' => ['Solicitud rechazada', 'Consulta las observaciones visibles y el correo recibido para conocer el motivo.'],
                        default => ['Estado de tu solicitud', 'Consulta el historial y las observaciones para conocer los avances.'],
                    };
                @endphp
                <section aria-labelledby="estado-actual" class="mt-6 rounded-2xl bg-neza-wine-soft p-5">
                    <p class="text-xs font-bold uppercase tracking-wide text-neza-wine">Estado actual</p>
                    <div class="mt-2 flex flex-wrap items-center gap-3"><h2 id="estado-actual" class="text-xl font-bold text-neza-ink">{{ $tituloEstado }}</h2>@include('partials.publico.solicitud-estado', ['estado' => $solicitud->estado])</div>
                    <p class="mt-2 text-sm leading-relaxed text-neza-ink">{{ $siguientePaso }}</p>
                </section>
                <dl class="mt-6 grid gap-5 border-t border-neza-line pt-5 sm:grid-cols-2"><div><dt class="text-xs text-neza-muted">Fecha de envío</dt><dd class="mt-1 font-bold">{{ $solicitud->fecha_envio?->format('d/m/Y H:i') ?? 'Sin fecha' }}</dd></div><div><dt class="text-xs text-neza-muted">Fecha de terminación</dt><dd class="mt-1 font-bold">{{ $solicitud->fecha_terminacion?->format('d/m/Y H:i') ?? 'Pendiente' }}</dd></div></dl>
                @if ($solicitud->estado->clave === 'APROBADA' && $solicitud->documentoEmitido)
                    <div class="mt-6 border-t border-neza-line pt-5">
                        @if ($solicitud->documentoEmitido->expirado())
                            <p class="text-sm font-semibold text-rose-700">La descarga del documento final ya venció.</p>
                        @else
                            <a href="{{ route('consulta.documentos.descargar', $solicitud->documentoEmitido) }}" class="inline-flex rounded-xl bg-neza-wine px-5 py-3 text-sm font-bold text-white hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Descargar documento final</a>
                            @if ($solicitud->documentoEmitido->fecha_expiracion)<p class="mt-2 text-xs text-neza-muted">Disponible hasta el {{ $solicitud->documentoEmitido->fecha_expiracion->format('d/m/Y H:i') }}.</p>@endif
                        @endif
                    </div>
                @endif
            </div>
            <div class="mt-6 grid gap-6 md:grid-cols-2">
                <section class="rounded-2xl border border-neza-line bg-white p-6 shadow-sm" aria-labelledby="historial-titulo"><h2 id="historial-titulo" class="text-lg font-bold">Historial de estados</h2><ol class="mt-5 space-y-4 border-l-2 border-neza-line pl-5">@forelse ($historial as $evento)<li><p class="font-semibold text-neza-ink">{{ $evento->estado }}</p><p class="mt-1 text-xs text-neza-muted">{{ \Illuminate\Support\Carbon::parse($evento->fecha)->format('d/m/Y H:i') }}</p></li>@empty<li class="text-sm text-neza-muted">Aún no hay movimientos registrados.</li>@endforelse</ol></section>
                <section class="rounded-2xl border border-neza-line bg-white p-6 shadow-sm" aria-labelledby="observaciones-titulo"><h2 id="observaciones-titulo" class="text-lg font-bold">Observaciones para ti</h2><div class="mt-5 space-y-4">@forelse ($observaciones as $observacion)<article class="rounded-xl bg-neza-canvas p-4"><p class="whitespace-pre-line text-sm text-neza-ink">{{ $observacion->texto }}</p><p class="mt-2 text-xs text-neza-muted">{{ \Illuminate\Support\Carbon::parse($observacion->created_at)->format('d/m/Y H:i') }}</p></article>@empty<p class="text-sm text-neza-muted">No hay observaciones públicas para esta solicitud.</p>@endforelse</div></section>
            </div>
        </div>
    </section>
@endsection
