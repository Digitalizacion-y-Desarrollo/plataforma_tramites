@extends('layouts.publico')

@section('title', $tramite?->nombre ?? 'Trámite no disponible')

@section('content')
    @if (! $tramite)
        <section class="bg-neza-canvas px-4 py-20"><div class="mx-auto max-w-xl rounded-2xl border border-neza-line bg-white p-8 text-center"><h1 class="text-2xl font-bold">Trámite no disponible</h1><p class="mt-3 text-sm text-neza-muted">La información del trámite todavía no está disponible.</p></div></section>
    @else
        @php
            $modalidades = collect($tramite->modalidades ?? [])->filter()->map(fn ($valor) => $valor === 'EN_LINEA' ? 'En línea' : 'Presencial');
            $datosResumen = collect(['Duración' => $tramite->duracion, 'Costo' => $tramite->costo, 'Tiempo estimado' => $tramite->tiempo_estimado])->filter(fn ($valor) => filled($valor));
            $requisitos = collect($tramite->requisitos ?? [])->filter(fn ($requisito) => filled($requisito));
            $proceso = collect($tramite->proceso ?? [])->filter(fn ($paso) => filled($paso));
            $preguntasFrecuentes = collect($tramite->preguntas_frecuentes ?? [])->filter(fn ($pregunta) => filled(data_get($pregunta, 'pregunta')) && filled(data_get($pregunta, 'respuesta')));
        @endphp
        <section class="border-b border-neza-line bg-white px-4 py-8 sm:px-6 lg:px-[72px]">
            <div class="mx-auto max-w-[1296px]">
                <nav class="text-xs text-neza-muted"><a href="{{ route('portal.publico') }}" class="font-bold text-neza-wine">Inicio</a><span class="mx-2">/</span><a href="{{ route('portal.tramites.index') }}">Trámites</a><span class="mx-2">/</span>{{ $tramite->nombre }}</nav>
                <h1 class="mt-6 text-3xl font-bold sm:text-[40px]">{{ $tramite->nombre }}</h1>
                @if (filled($tramite->descripcion))<p class="mt-2 max-w-3xl text-sm text-neza-muted">{{ $tramite->descripcion }}</p>@endif
                @if ($datosResumen->isNotEmpty())<div class="mt-6 grid gap-3 rounded-xl border border-neza-line bg-neza-canvas p-5 sm:grid-cols-3">@foreach ($datosResumen as $etiqueta => $valor)<div><p class="text-xs font-bold text-neza-muted">{{ $etiqueta }}</p><p class="mt-1 text-sm font-bold">{{ $valor }}</p></div>@endforeach</div>@endif
            </div>
        </section>
        <section class="bg-neza-canvas px-4 py-8 sm:px-6 lg:px-[72px]">
            <div class="mx-auto grid max-w-[1296px] gap-6 lg:grid-cols-[1fr_300px]">
                <div class="space-y-5">
                    @if ($requisitos->isNotEmpty())<article class="rounded-2xl border border-neza-line bg-white p-6"><h2 class="text-2xl font-bold">Requisitos</h2><ul class="mt-4 space-y-3 text-sm">@foreach ($requisitos as $requisito)<li class="flex gap-3"><svg class="mt-0.5 size-4 shrink-0 text-emerald-700" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>{{ $requisito }}</li>@endforeach</ul></article>@endif
                    @if ($proceso->isNotEmpty())<article class="rounded-2xl border border-neza-line bg-white p-6"><h2 class="text-2xl font-bold">Proceso</h2><ol class="mt-4 space-y-4">@foreach ($proceso as $paso)<li class="flex gap-3 text-sm"><span class="grid size-7 shrink-0 place-items-center rounded-full bg-neza-wine text-xs font-bold text-white">{{ $loop->iteration }}</span>{{ $paso }}</li>@endforeach</ol></article>@endif
                    @if ($preguntasFrecuentes->isNotEmpty())<article class="rounded-2xl border border-neza-line bg-white p-6"><h2 class="text-2xl font-bold">Preguntas frecuentes</h2><div class="mt-4 space-y-2">@foreach ($preguntasFrecuentes as $pregunta)<details class="rounded-xl border border-neza-line px-4 py-3"><summary class="cursor-pointer text-sm font-bold">{{ data_get($pregunta, 'pregunta') }}</summary><p class="mt-3 border-t border-neza-line pt-3 text-sm text-neza-muted">{{ data_get($pregunta, 'respuesta') }}</p></details>@endforeach</div></article>@endif
                </div>
                @if ($modalidades->isNotEmpty() || $formularioVersion)
                    <aside class="h-fit rounded-2xl border border-neza-line bg-white p-5">
                        @if ($modalidades->isNotEmpty())<h2 class="text-lg font-bold">Modalidad</h2><p class="mt-2 text-sm text-neza-muted">{{ $modalidades->join(' y ') }}</p>@endif
                        @if ($formularioVersion)<a href="{{ route('portal.tramites.iniciar', $tramite) }}" @class(['flex h-12 items-center justify-center rounded-xl bg-neza-wine text-sm font-bold text-white hover:bg-[#4c0f26]', 'mt-5' => $modalidades->isNotEmpty()])>Iniciar trámite</a>@endif
                    </aside>
                @endif
            </div>
        </section>
    @endif
@endsection
