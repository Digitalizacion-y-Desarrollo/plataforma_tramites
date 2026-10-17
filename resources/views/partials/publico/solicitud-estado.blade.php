@php
    $color = match ($estado?->clave) {
        'APROBADA' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        'RECHAZADA' => 'bg-rose-50 text-rose-800 ring-rose-200',
        'CORRECCION_SOLICITADA' => 'bg-amber-50 text-amber-900 ring-amber-200',
        'ENVIADA' => 'bg-blue-50 text-blue-800 ring-blue-200',
        default => 'bg-neza-canvas text-neza-ink ring-neza-line',
    };
@endphp
<span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset {{ $color }}">{{ $estado?->nombre ?? 'Sin estado' }}</span>
