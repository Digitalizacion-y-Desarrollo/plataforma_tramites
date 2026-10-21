<form action="{{ $rutaEstadisticas }}" method="GET" class="rounded-2xl border border-neza-line bg-white p-3 shadow-sm" aria-label="Periodo de estadísticas">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <span class="px-2 text-xs font-bold uppercase tracking-[0.12em] text-neza-muted">Periodo</span>
            @foreach (['DIA' => 'Día', 'SEMANA' => 'Semana', 'MES' => 'Mes'] as $valor => $etiqueta)
                <a href="{{ $rutaEstadisticas }}?estadistica_periodo={{ $valor }}" @class(['rounded-lg px-3 py-2 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine', 'bg-neza-wine text-white shadow-sm' => $filtroEstadisticas['periodo'] === $valor, 'text-neza-muted hover:bg-neza-canvas hover:text-neza-ink' => $filtroEstadisticas['periodo'] !== $valor])>{{ $etiqueta }}</a>
            @endforeach
        </div>
        <div class="flex flex-wrap items-end gap-2">
            <input type="hidden" name="estadistica_periodo" value="RANGO">
            <div><label for="estadistica-fecha-desde" class="mb-1 block text-xs font-semibold text-neza-muted">Desde</label><input id="estadistica-fecha-desde" type="date" name="estadistica_fecha_desde" value="{{ $filtroEstadisticas['fecha_desde'] }}" required class="h-10 rounded-lg border border-neza-line px-3 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"></div>
            <div><label for="estadistica-fecha-hasta" class="mb-1 block text-xs font-semibold text-neza-muted">Hasta</label><input id="estadistica-fecha-hasta" type="date" name="estadistica_fecha_hasta" value="{{ $filtroEstadisticas['fecha_hasta'] }}" required class="h-10 rounded-lg border border-neza-line px-3 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"></div>
            <button type="submit" class="h-10 rounded-lg border border-neza-line px-3 text-sm font-semibold text-neza-ink hover:bg-neza-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Aplicar rango</button>
        </div>
    </div>
    @if ($filtroEstadisticas['periodo'] === 'RANGO')<p class="mt-2 px-2 text-xs font-medium text-neza-wine">Mostrando estadísticas del {{ \Carbon\Carbon::parse($filtroEstadisticas['fecha_desde'])->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($filtroEstadisticas['fecha_hasta'])->format('d/m/Y') }}.</p>@endif
</form>
