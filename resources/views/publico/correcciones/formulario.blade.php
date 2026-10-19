@extends('layouts.publico')

@section('title', 'Corregir documentos')

@section('content')
    <section class="bg-neza-canvas px-4 py-12 sm:px-6 lg:px-[72px]">
        <div class="mx-auto max-w-3xl rounded-2xl border border-neza-line bg-white p-6 shadow-sm sm:p-8">
            <p class="text-xs font-bold uppercase tracking-wide text-neza-wine">Corrección de documentos</p>
            <h1 class="mt-2 text-3xl font-bold text-neza-ink">Reemplaza los archivos solicitados</h1>
            <p class="mt-3 text-sm text-neza-muted">Carga un archivo nuevo para cada documento. Al enviarlos, la solicitud volverá a revisión.</p>
            <form action="{{ route('correcciones.actualizar', $token) }}" method="POST" enctype="multipart/form-data" class="mt-8 space-y-6">
                @csrf
                @foreach ($documentos as $documento)
                    <article class="rounded-xl border border-neza-line p-5">
                        <h2 class="text-base font-bold text-neza-ink">{{ $documento->etiqueta }}</h2>
                        <div class="mt-3 rounded-lg bg-amber-50 p-4 text-sm text-amber-950"><p class="font-semibold">Motivo de corrección</p><p class="mt-1 whitespace-pre-wrap">{{ $documento->motivo }}</p></div>
                        <label for="archivo-{{ $documento->campo_id }}" class="mt-5 block text-sm font-semibold text-neza-ink">Archivo corregido <span class="text-rose-700">*</span></label>
                        <input id="archivo-{{ $documento->campo_id }}" data-file-input name="archivos[{{ $documento->campo_id }}]" type="file" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" @error('archivos.'.$documento->campo_id) aria-invalid="true" @enderror class="mt-2 block w-full rounded-lg border border-neza-line bg-white px-3 py-2 text-sm text-neza-muted file:mr-4 file:rounded-md file:border-0 file:bg-neza-wine-soft file:px-3 file:py-2 file:text-xs file:font-bold file:text-neza-wine @error('archivos.'.$documento->campo_id) border-rose-600 @enderror">
                        <p class="mt-2 text-xs text-neza-muted">PDF, JPG, JPEG, PNG, DOC o DOCX. Máximo 10 MB.</p>
                        @error('archivos.'.$documento->campo_id)<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </article>
                @endforeach
                <button type="submit" class="min-h-11 min-w-40 rounded-lg bg-neza-wine px-5 py-3 text-sm font-semibold text-white hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Enviar documentos corregidos</button>
            </form>
        </div>
    </section>
@endsection
