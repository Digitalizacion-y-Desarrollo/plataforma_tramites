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
                        'ENVIADA' => ['Solicitud recibida', 'Siguiente paso: espera la revisión del área responsable. Te avisaremos por correo cuando haya un cambio.'],
                        'CORRECCION_SOLICITADA' => ['Se requiere una corrección', 'Siguiente paso: usa el enlace del correo recibido para subir los documentos corregidos. Tu solicitud volverá a revisión.'],
                        'VALIDADA_PARA_PAGO' => ['Información validada', 'Siguiente paso: el área responsable generará tu referencia de pago.'],
                        'EN_ESPERA_DE_PAGO' => ['En espera de pago', 'Siguiente paso: realiza el pago con la referencia enviada a tu correo antes de su vencimiento.'],
                        'EN_REVISION_FINAL' => ['Pago validado', 'Siguiente paso: el área responsable emitirá la resolución final de tu solicitud.'],
                        'APROBADA' => ['Solicitud aprobada', 'Siguiente paso: descarga tu documento final desde esta pantalla mientras esté disponible.'],
                        'RECHAZADA' => ['Solicitud rechazada', 'Consulta las observaciones visibles y el correo recibido para conocer el motivo. Si tienes dudas, usa los canales de atención oficial.'],
                        default => ['Estado de tu solicitud', 'Consulta el historial y las observaciones para conocer los avances.'],
                    };
                @endphp
                <section aria-labelledby="estado-actual" class="mt-6 rounded-2xl bg-neza-wine-soft p-5">
                    <p class="text-xs font-bold uppercase tracking-wide text-neza-wine">Estado actual</p>
                    <div class="mt-2 flex flex-wrap items-center gap-3"><h2 id="estado-actual" class="text-xl font-bold text-neza-ink">{{ $tituloEstado }}</h2>@include('partials.publico.solicitud-estado', ['estado' => $solicitud->estado])</div>
                    <p class="mt-2 text-sm leading-relaxed text-neza-ink">{{ $siguientePaso }}</p>
                </section>
                <section aria-labelledby="progreso-titulo" class="mt-5">
                    <h2 id="progreso-titulo" class="text-base font-bold">Progreso de tu solicitud</h2>
                    <div class="mt-3 rounded-2xl border border-neza-line bg-white p-5">@include('partials.publico.solicitud-progreso', ['estado' => $solicitud->estado, 'flujoPago' => $solicitud->flujo_pago])</div>
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
                @if ($solicitud->estado->clave === 'EN_ESPERA_DE_PAGO' && $pago)
                    <div class="mt-6 border-t border-neza-line pt-5">
                        <p class="text-sm font-semibold text-neza-ink">Tu referencia de pago está lista.</p>
                        <p class="mt-1 text-sm text-neza-muted">Realiza el pago antes del {{ $pago->fecha_vencimiento->format('d/m/Y') }} para continuar con tu trámite.</p>
                        <a href="{{ route('consulta.pagos.referencia.descargar', $pago) }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-neza-wine px-5 py-3 text-sm font-bold text-white hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine"><svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5v-9m0 9-3.75-3.75M12 16.5l3.75-3.75M3.75 17.25v1.5A2.25 2.25 0 0 0 6 21h12a2.25 2.25 0 0 0 2.25-2.25v-1.5" /></svg>Descargar referencia de pago</a>
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
