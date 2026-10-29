@extends('layouts.publico')

@section('title', 'Solicitud enviada')

@section('content')
    <section class="bg-neza-canvas px-4 py-12 sm:px-6 lg:px-[72px]">
        <div class="mx-auto max-w-xl rounded-2xl border border-neza-line bg-white p-6 text-center shadow-sm sm:p-10" role="status">
            <svg class="mx-auto size-12 text-neza-wine" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <h1 class="mt-4 text-2xl font-bold text-neza-ink">Solicitud enviada</h1>
            <p class="mt-3 text-sm text-neza-muted">Tu solicitud fue enviada correctamente. Conserva tu folio para futuras consultas.</p>
            <div class="mt-6 rounded-xl border border-neza-line bg-neza-canvas p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-neza-muted">Folio de solicitud</p>
                <p class="mt-2 break-all text-xl font-bold text-neza-wine">{{ $folio }}</p>
            </div>
            <a href="{{ route('portal.publico') }}" class="mt-6 inline-flex min-h-12 items-center justify-center rounded-xl bg-neza-wine px-6 text-sm font-bold text-white hover:bg-neza-wine/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Regresar al portal de trámites</a>
        </div>
    </section>
@endsection
