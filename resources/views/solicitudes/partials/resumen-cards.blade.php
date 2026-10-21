@php
    $totalSeguro = max(1, $totalSolicitudes);
    $indicadores = [
        ['Por revisar', $solicitudesEnviadas, 'Pendientes de atención', 'bg-amber-50 text-amber-800', 'accent-amber-500', 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['Aprobadas', $solicitudesAprobadas, 'Resolución favorable', 'bg-emerald-50 text-emerald-700', 'accent-emerald-600', 'm4.5 12.75 4.5 4.5 10.5-10.5'],
        ['Rechazadas', $solicitudesRechazadas, 'Resolución no favorable', 'bg-rose-50 text-rose-700', 'accent-rose-600', 'M6 18 18 6M6 6l12 12'],
    ];
@endphp

@include('solicitudes.partials.filtros-estadisticas')

<section class="grid gap-4 lg:grid-cols-4" aria-label="Resumen operativo de solicitudes">
    <article class="relative overflow-hidden rounded-2xl bg-neza-wine p-6 text-white shadow-lg shadow-neza-wine/15 lg:col-span-1">
        <div class="absolute -right-8 -top-10 size-36 rounded-full border-[18px] border-white/10" aria-hidden="true"></div>
        <div class="relative"><div class="flex items-center justify-between"><p class="text-sm font-semibold text-white/80">Expedientes totales</p><span class="grid size-10 place-items-center rounded-xl bg-white/15"><svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5h18M3 6.75h18M3 20.25h18" /></svg></span></div><p class="mt-8 text-5xl font-bold tracking-tight">{{ number_format($totalSolicitudes) }}</p><p class="mt-2 text-sm text-white/75">Solicitudes registradas</p></div>
    </article>

    @foreach ($indicadores as [$etiqueta, $cantidad, $descripcion, $iconoColor, $acento, $trazo])
        @php($porcentaje = $totalSolicitudes > 0 ? round(($cantidad / $totalSolicitudes) * 100) : 0)
        <article class="rounded-2xl border border-neza-line bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between gap-3"><div><p class="text-sm font-semibold text-neza-ink">{{ $etiqueta }}</p><p class="mt-1 text-xs text-neza-muted">{{ $descripcion }}</p></div><span class="grid size-9 shrink-0 place-items-center rounded-xl {{ $iconoColor }}"><svg class="size-[18px]" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $trazo }}" /></svg></span></div>
            <div class="mt-7 flex items-end justify-between gap-3"><p class="text-3xl font-bold tracking-tight text-neza-ink">{{ number_format($cantidad) }}</p><p class="text-xs font-semibold text-neza-muted">{{ $porcentaje }}%</p></div>
            <progress value="{{ $cantidad }}" max="{{ $totalSeguro }}" class="mt-3 h-1.5 w-full overflow-hidden rounded-full {{ $acento }}" aria-label="{{ $etiqueta }}: {{ $porcentaje }} por ciento del total"></progress>
        </article>
    @endforeach
</section>
