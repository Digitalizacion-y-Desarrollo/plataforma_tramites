@php
    $clave = $estado?->clave;
    $enCorreccion = $clave === 'CORRECCION_SOLICITADA';
    $pasos = collect([
        ['clave' => $clave !== 'BORRADOR' && $clave !== null, 'titulo' => 'Enviada', 'descripcion' => 'Tu solicitud fue recibida por el municipio.'],
        $enCorreccion ? ['clave' => true, 'titulo' => 'En corrección', 'descripcion' => 'El área solicitó corregir documentos o información.'] : null,
        ['clave' => $clave === 'APROBADA' || $clave === 'RECHAZADA', 'titulo' => $clave === 'RECHAZADA' ? 'Rechazada' : 'Aprobada', 'descripcion' => $clave === 'RECHAZADA' ? 'La revisión concluyó sin autorización.' : 'Tu tramitación concluyó favorablemente.'],
    ])->filter()->values();
    $indiceActual = $pasos->filter(fn ($paso) => $paso['clave'] === true)->keys()->last();
@endphp
<ol class="space-y-5" aria-label="Progreso de la solicitud">
    @foreach ($pasos as $paso)
        @php($actual = $loop->index === $indiceActual)
        @php($completado = $loop->index < $indiceActual)
        @php($pendiente = $loop->index > $indiceActual)
        <li class="relative flex gap-4" @if ($actual) aria-current="step" @endif>
            @if (! $loop->last)
                <span class="absolute left-[11px] top-6 h-[calc(100%-1.5rem)] w-0.5 {{ $pendiente ? 'bg-neza-line' : 'bg-neza-wine' }}" aria-hidden="true"></span>
            @endif
            <span class="z-10 mt-0.5 grid size-6 shrink-0 place-items-center rounded-full text-[11px] font-bold {{ $actual || $completado ? 'bg-neza-wine text-white' : 'border-2 border-neza-line bg-white text-neza-muted' }}" aria-hidden="true">
                @if ($completado)
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                @else
                    {{ $loop->iteration }}
                @endif
            </span>
            <div>
                <p class="text-sm font-bold {{ $actual ? 'text-neza-wine' : ($completado ? 'text-neza-ink' : 'text-neza-muted') }}">{{ $paso['titulo'] }} @if ($actual)<span class="sr-only">(paso actual)</span>@endif</p>
                <p class="mt-0.5 text-xs text-neza-muted">{{ $paso['descripcion'] }}</p>
            </div>
        </li>
    @endforeach
</ol>
