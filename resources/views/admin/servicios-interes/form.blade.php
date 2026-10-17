@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <a href="{{ route('servicios-interes.index') }}" class="text-sm font-semibold text-neza-wine hover:underline">Volver a servicios</a>
            <h1 class="mt-3 text-3xl font-bold text-neza-ink">{{ $servicio ? 'Editar servicio' : 'Nuevo servicio' }}</h1>
            <p class="mt-2 text-sm text-neza-muted">El título, la descripción y el logo se mostrarán en la página principal.</p>
        </div>
        <form action="{{ $servicio ? route('servicios-interes.update', $servicio) : route('servicios-interes.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5 rounded-xl border border-neza-line bg-white p-5 shadow-sm sm:p-7">
            @csrf
            @if ($servicio)
                @method('PUT')
            @endif
            <div>
                <label for="titulo" class="mb-1.5 block text-sm font-semibold text-neza-ink">Título</label>
                <input id="titulo" name="titulo" type="text" maxlength="160" required value="{{ old('titulo', $servicio?->titulo) }}" @class(['w-full rounded-lg border px-3 py-2.5 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine', 'border-rose-500' => $errors->has('titulo'), 'border-neza-line' => ! $errors->has('titulo')])>
                @error('titulo') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="descripcion" class="mb-1.5 block text-sm font-semibold text-neza-ink">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="4" maxlength="500" required @class(['w-full rounded-lg border px-3 py-2.5 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine', 'border-rose-500' => $errors->has('descripcion'), 'border-neza-line' => ! $errors->has('descripcion')])>{{ old('descripcion', $servicio?->descripcion) }}</textarea>
                @error('descripcion') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="enlace" class="mb-1.5 block text-sm font-semibold text-neza-ink">Enlace</label>
                <input id="enlace" name="enlace" type="url" maxlength="2048" required placeholder="https://ejemplo.gob.mx/servicio" value="{{ old('enlace', $servicio?->enlace) }}" @class(['w-full rounded-lg border px-3 py-2.5 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine', 'border-rose-500' => $errors->has('enlace'), 'border-neza-line' => ! $errors->has('enlace')])>
                @error('enlace') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="logo" class="mb-1.5 block text-sm font-semibold text-neza-ink">Logo {{ $servicio ? '(opcional para conservar el actual)' : '' }}</label>
                @if ($servicio)
                    <img src="{{ asset('storage/'.$servicio->logo_storage_key) }}" alt="Logo actual de {{ $servicio->titulo }}" class="mb-3 size-20 rounded-lg border border-neza-line object-contain p-1">
                @endif
                <input id="logo" name="logo" type="file" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" {{ $servicio ? '' : 'required' }} @class(['block w-full rounded-lg border px-3 py-2.5 text-sm file:mr-3 file:border-0 file:bg-neza-wine-soft file:px-3 file:py-1 file:text-neza-wine focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine', 'border-rose-500' => $errors->has('logo'), 'border-neza-line' => ! $errors->has('logo')])>
                <p class="mt-1 text-xs text-neza-muted">PNG, JPG o WebP. Máximo 2 MB.</p>
                @error('logo') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div class="flex flex-wrap justify-end gap-3 border-t border-neza-line pt-5">
                <a href="{{ route('servicios-interes.index') }}" class="rounded-lg border border-neza-line px-4 py-2.5 text-sm font-semibold text-neza-ink hover:bg-neza-canvas">Cancelar</a>
                <button type="submit" class="rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">{{ $servicio ? 'Guardar cambios' : 'Crear servicio' }}</button>
            </div>
        </form>
    </div>
@endsection
