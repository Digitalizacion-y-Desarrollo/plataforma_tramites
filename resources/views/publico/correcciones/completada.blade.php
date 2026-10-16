@extends('layouts.publico')

@section('title', 'Corrección enviada')

@section('content')
    <section class="bg-neza-canvas px-4 py-12 sm:px-6 lg:px-[72px]"><div class="mx-auto max-w-lg rounded-2xl border border-emerald-200 bg-white p-8 text-center shadow-sm"><h1 class="text-2xl font-bold text-neza-ink">Documentos enviados</h1><p class="mt-3 text-sm text-neza-muted">Recibimos los archivos corregidos. Tu solicitud volverá a ser revisada por el área responsable.</p><a href="{{ route('portal.publico') }}" class="mt-6 inline-flex rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Ir al portal</a></div></section>
@endsection
