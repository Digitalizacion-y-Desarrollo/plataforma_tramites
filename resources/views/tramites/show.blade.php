@extends('layouts.app')

@section('content')
    @php
        $estadoClave = $tramite->estado?->clave ?? 'BORRADOR';
        $formularioActivo = $tramite->formularioActivo->first();
        $estadoEstilo = match ($estadoClave) {
            'PUBLICADO' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'ARCHIVADO' => 'bg-slate-100 text-slate-600 ring-slate-200',
            default => 'bg-amber-50 text-amber-800 ring-amber-200',
        };
        $detalles = collect([
            'Documento a obtener' => $tramite->doc_a_obtener,
            'Vigencia del documento' => $tramite->vigencia_doc,
            'Sujeto de inspección' => $tramite->sujeto_inspeccion,
            'Criterios de resolución' => $tramite->criterios_resolucion,
            'Unidad administrativa responsable' => $tramite->unidad_administrativa_responsable,
            'Fundamento jurídico' => $tramite->fund_juridico,
        ])->filter(fn ($valor) => filled($valor));
        $modalidades = collect($tramite->modalidades ?? [])->map(fn ($modalidad) => $modalidad === 'EN_LINEA' ? 'En línea' : 'Presencial');
        $requisitosGuardados = collect($tramite->requisitos ?? []);
        $requisitos = $requisitosGuardados->isNotEmpty() && is_string($requisitosGuardados->first())
            ? collect([['titulo' => 'Requisitos generales', 'requisitos' => $requisitosGuardados->filter(fn ($requisito) => filled($requisito))->values()->all()]])
            : $requisitosGuardados
                ->filter(fn ($seccion) => is_array($seccion) && filled(data_get($seccion, 'titulo')))
                ->map(fn ($seccion) => ['titulo' => data_get($seccion, 'titulo'), 'requisitos' => collect(data_get($seccion, 'requisitos', []))->filter(fn ($requisito) => filled($requisito))->values()->all()])
                ->filter(fn ($seccion) => $seccion['requisitos'] !== [])
                ->values();
        $proceso = collect($tramite->proceso ?? [])->filter(fn ($paso) => filled($paso));
        $preguntas = collect($tramite->preguntas_frecuentes ?? [])->filter(fn ($pregunta) => filled(data_get($pregunta, 'pregunta')) && filled(data_get($pregunta, 'respuesta')));
    @endphp

    <div class="mx-auto max-w-6xl space-y-6">
        <nav aria-label="Migas de pan" class="flex items-center gap-2 text-sm text-neza-muted"><a href="{{ route('catalogo-tramites.index') }}" class="font-semibold text-neza-wine hover:underline">Catálogo de trámites</a><svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" /></svg><span class="truncate">{{ $tramite->nombre }}</span></nav>

        <section class="overflow-hidden rounded-2xl border border-neza-line bg-white shadow-sm">
            <div class="border-b border-neza-line bg-gradient-to-br from-neza-wine-soft via-white to-white px-5 py-6 sm:px-8 sm:py-7">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><span class="rounded-md bg-neza-wine px-2.5 py-1 font-mono text-xs font-bold text-white">{{ $tramite->clave }}</span><span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $estadoEstilo }}">{{ $tramite->estado?->nombre ?? 'Borrador' }}</span><span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-emerald-50 text-emerald-700' => $tramite->activo, 'bg-slate-100 text-slate-600' => ! $tramite->activo])>{{ $tramite->activo ? 'Habilitado' : 'Deshabilitado' }}</span></div><h1 class="mt-4 text-3xl font-bold tracking-tight text-neza-ink sm:text-4xl">{{ $tramite->nombre }}</h1>@if (filled($tramite->descripcion))<p class="mt-3 max-w-3xl text-sm leading-6 text-neza-muted">{{ $tramite->descripcion }}</p>@endif</div>
                    <a href="{{ route('catalogo-tramites.index') }}" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-neza-wine px-4 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine"><svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>Editar trámite</a>
                </div>
            </div>
            <dl class="grid divide-y divide-neza-line sm:grid-cols-2 sm:divide-x sm:divide-y-0 sm:[&>div:nth-child(n+3)]:border-t sm:[&>div:nth-child(n+3)]:border-neza-line lg:grid-cols-4 lg:[&>div:nth-child(n+3)]:border-t-0"><div class="p-5"><dt class="text-xs font-bold uppercase tracking-wide text-neza-muted">Dependencia</dt><dd class="mt-2 break-words text-sm font-semibold text-neza-ink">{{ $tramite->departamento_nombre_snapshot ?: 'Sin departamento' }}</dd></div><div class="p-5"><dt class="text-xs font-bold uppercase tracking-wide text-neza-muted">Formulario</dt><dd class="mt-2 break-words text-sm font-semibold text-neza-ink">{{ $formularioActivo?->formulario?->nombre ?: 'Sin formulario' }}</dd></div><div class="p-5"><dt class="text-xs font-bold uppercase tracking-wide text-neza-muted">Tiempo estimado</dt><dd class="mt-2 break-words text-sm font-semibold text-neza-ink">{{ $tramite->tiempo_estimado ?: 'No especificado' }}</dd></div><div class="p-5"><dt class="text-xs font-bold uppercase tracking-wide text-neza-muted">Costo</dt><dd class="mt-2 break-words text-sm font-semibold text-neza-ink">{{ $tramite->costo_texto ?: 'Sin costo' }}</dd></div></dl>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_300px]">
            <div class="space-y-6">
                <section class="rounded-2xl border border-neza-line bg-white p-5 shadow-sm sm:p-6"><div class="flex items-start gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-neza-wine-soft text-neza-wine"><svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6" /></svg></span><div><h2 class="text-xl font-bold text-neza-ink">Información del documento</h2><p class="mt-0.5 text-sm text-neza-muted">Datos administrativos del trámite.</p></div></div><dl class="mt-6 divide-y divide-neza-line">@forelse ($detalles as $etiqueta => $valor)<div class="grid gap-2 py-4 first:pt-0 last:pb-0 sm:grid-cols-[220px_minmax(0,1fr)]"><dt class="text-sm font-bold text-neza-ink">{{ $etiqueta }}</dt><dd class="break-words whitespace-pre-line text-sm leading-6 text-neza-muted">{{ $valor }}</dd></div>@empty<div class="rounded-xl bg-neza-canvas p-5 text-center text-sm text-neza-muted">No hay información administrativa registrada.</div>@endforelse</dl></section>
                @if ($requisitos->isNotEmpty() || $proceso->isNotEmpty() || $preguntas->isNotEmpty())
                    <section class="rounded-2xl border border-neza-line bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-neza-ink">Contenido público configurado</h2>
                        <div class="mt-5 grid gap-4 sm:grid-cols-3"><div class="rounded-xl bg-neza-canvas p-4"><p class="text-2xl font-bold text-neza-wine">{{ $requisitos->count() }}</p><p class="mt-1 text-xs font-semibold text-neza-muted">Secciones de requisitos</p></div><div class="rounded-xl bg-neza-canvas p-4"><p class="text-2xl font-bold text-neza-wine">{{ $proceso->count() }}</p><p class="mt-1 text-xs font-semibold text-neza-muted">Pasos de proceso</p></div><div class="rounded-xl bg-neza-canvas p-4"><p class="text-2xl font-bold text-neza-wine">{{ $preguntas->count() }}</p><p class="mt-1 text-xs font-semibold text-neza-muted">Preguntas frecuentes</p></div></div>
                        <div class="mt-6 space-y-6 border-t border-neza-line pt-6">
                            @if ($requisitos->isNotEmpty())<section><h3 class="text-base font-bold text-neza-ink">Requisitos</h3><div class="mt-3 space-y-4">@foreach ($requisitos as $seccion)<div><h4 class="text-sm font-semibold text-neza-ink">{{ $seccion['titulo'] }}</h4><ul class="mt-2 space-y-2 text-sm text-neza-muted">@foreach ($seccion['requisitos'] as $requisito)<li class="flex gap-2"><svg class="mt-0.5 size-4 shrink-0 text-emerald-700" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg><span class="whitespace-pre-line">{{ $requisito }}</span></li>@endforeach</ul></div>@endforeach</div></section>@endif
                            @if ($proceso->isNotEmpty())<section><h3 class="text-base font-bold text-neza-ink">Proceso</h3><ol class="mt-3 space-y-3">@foreach ($proceso as $paso)<li class="flex gap-3 text-sm text-neza-muted"><span class="grid size-6 shrink-0 place-items-center rounded-full bg-neza-wine text-xs font-bold text-white">{{ $loop->iteration }}</span><span class="pt-0.5 whitespace-pre-line">{{ $paso }}</span></li>@endforeach</ol></section>@endif
                            @if ($preguntas->isNotEmpty())<section><h3 class="text-base font-bold text-neza-ink">Preguntas frecuentes</h3><div class="mt-3 space-y-2">@foreach ($preguntas as $pregunta)<details class="rounded-xl border border-neza-line px-4 py-3"><summary class="cursor-pointer text-sm font-semibold text-neza-ink">{{ data_get($pregunta, 'pregunta') }}</summary><p class="mt-3 border-t border-neza-line pt-3 text-sm leading-6 text-neza-muted whitespace-pre-line">{{ data_get($pregunta, 'respuesta') }}</p></details>@endforeach</div></section>@endif
                        </div>
                    </section>
                @endif
            </div>
            <aside class="space-y-5"><section class="rounded-2xl border border-neza-line bg-white p-5 shadow-sm"><h2 class="text-base font-bold text-neza-ink">Resumen operativo</h2><dl class="mt-4 space-y-4 text-sm"><div><dt class="text-xs font-semibold text-neza-muted">Modalidad</dt><dd class="mt-1 font-semibold text-neza-ink">{{ $modalidades->isNotEmpty() ? $modalidades->join(' y ') : 'No especificada' }}</dd></div><div><dt class="text-xs font-semibold text-neza-muted">Flujo de pago</dt><dd class="mt-1 font-semibold text-neza-ink">{{ str($tramite->flujo_pago ?? 'SIN_PAGO')->replace('_', ' ')->title() }}</dd></div>@if (filled($tramite->pago_vigencia_dias))<div><dt class="text-xs font-semibold text-neza-muted">Vigencia de pago</dt><dd class="mt-1 font-semibold text-neza-ink">{{ $tramite->pago_vigencia_dias }} días</dd></div>@endif<div><dt class="text-xs font-semibold text-neza-muted">Última actualización</dt><dd class="mt-1 font-semibold text-neza-ink">{{ $tramite->updated_at?->format('d/m/Y H:i') ?? 'Sin registro' }}</dd></div></dl></section><a href="{{ route('catalogo-tramites.index') }}" class="flex items-center justify-center gap-2 rounded-xl border border-neza-line bg-white px-4 py-3 text-sm font-semibold text-neza-ink shadow-sm transition hover:bg-neza-canvas"><svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 19.5-7.5-7.5 7.5-7.5" /></svg>Volver al catálogo</a></aside>
        </div>
    </div>
@endsection
