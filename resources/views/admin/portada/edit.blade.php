@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <section><p class="text-sm font-semibold text-neza-wine">Configuración</p><h1 class="mt-1 text-3xl font-bold tracking-tight text-neza-ink">Foto de portada</h1><p class="mt-2 text-sm text-neza-muted">Esta imagen se muestra en el encabezado del portal ciudadano.</p></section>
        <section class="rounded-2xl border border-neza-line bg-white p-5 shadow-sm">
            @if ($configuracion?->portada_storage_key)
                <img src="{{ Storage::disk('public')->url($configuracion->portada_storage_key) }}" alt="Portada actual del portal" class="mb-5 aspect-[16/6] w-full rounded-xl object-cover">
            @endif
            <form method="POST" action="{{ route('portada.update') }}" enctype="multipart/form-data" class="space-y-4">@csrf @method('PUT')
                <div><label for="portada" class="block text-sm font-semibold text-neza-ink">Imagen</label><input id="portada" name="portada" type="file" accept="image/png,image/jpeg,image/webp" required class="mt-2 block w-full text-sm text-neza-ink file:mr-4 file:rounded-lg file:border-0 file:bg-neza-wine-soft file:px-3 file:py-2 file:font-semibold file:text-neza-wine">@error('portada')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror<p class="mt-2 text-xs text-neza-muted">PNG, JPG o WEBP; máximo 5 MB.</p></div>
                <button type="submit" data-submit-lock class="inline-flex min-h-11 items-center justify-center rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Guardar portada</button>
            </form>
        </section>
    </div>
@endsection
