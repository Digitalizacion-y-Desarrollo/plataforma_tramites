@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-[1440px] space-y-6">
        <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-sm font-semibold text-neza-wine">{{ $dependencia['etiqueta'] }}</p><h1 class="mt-1 text-3xl font-bold tracking-tight text-neza-ink">Resumen de solicitudes</h1><p class="mt-2 text-sm text-neza-muted">Seguimiento de los expedientes asignados a esta dependencia.</p></div>
            <a href="{{ route('solicitudes.index') }}" class="inline-flex items-center justify-center rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Ver solicitudes</a>
        </section>
        @include('solicitudes.partials.resumen-cards')
    </div>
@endsection
