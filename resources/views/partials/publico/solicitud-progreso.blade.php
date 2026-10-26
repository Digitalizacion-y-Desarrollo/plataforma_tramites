@php
    $clave = $estado?->clave;
    $flujoPago = $flujoPago ?? 'SIN_PAGO';
    $esConPago = $flujoPago !== 'SIN_PAGO';

    $pasos = [
        [
            'estados' => ['ENVIADA'],
            'titulo' => 'Solicitud enviada',
            'descripcion' => 'Tu solicitud fue recibida por el municipio.',
        ],
    ];

    if ($clave === 'CORRECCION_SOLICITADA') {
        $pasos[] = [
            'estados' => ['CORRECCION_SOLICITADA'],
            'titulo' => 'Corrección solicitada',
            'descripcion' => 'Revisa el correo recibido y corrige los documentos solicitados.',
        ];
    }

    if ($flujoPago === 'PAGO_POST_VALIDACION') {
        $pasos[] = [
            'estados' => ['VALIDADA_PARA_PAGO'],
            'titulo' => 'Información validada',
            'descripcion' => 'El área responsable validó la información y enviará la ficha de pago.',
        ];
    }

    if ($esConPago) {
        $pasos[] = [
            'estados' => ['EN_ESPERA_DE_PAGO'],
            'titulo' => 'En espera de pago',
            'descripcion' => 'Realiza el pago con la ficha enviada a tu correo.',
        ];
        $pasos[] = [
            'estados' => ['EN_REVISION_FINAL'],
            'titulo' => 'Pago validado',
            'descripcion' => 'El pago fue validado y tu solicitud está en revisión final.',
        ];
    }

    $pasos[] = [
        'estados' => ['APROBADA', 'RECHAZADA'],
        'titulo' => $clave === 'RECHAZADA' ? 'Solicitud rechazada' : 'Resolución final',
        'descripcion' => $clave === 'RECHAZADA'
            ? 'La revisión concluyó sin autorización. Consulta las observaciones disponibles.'
            : 'El área responsable emitirá la resolución de tu solicitud.',
    ];

    $indiceActual = collect($pasos)->search(fn (array $paso): bool => in_array($clave, $paso['estados'], true));
    $indiceActual = $indiceActual === false ? null : $indiceActual;
@endphp

<ol class="space-y-5" aria-label="Progreso de la solicitud">
    @foreach ($pasos as $paso)
        @php($actual = $loop->index === $indiceActual)
        @php($completado = $indiceActual !== null && $loop->index < $indiceActual)
        @php($pendiente = ! $actual && ! $completado)
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
