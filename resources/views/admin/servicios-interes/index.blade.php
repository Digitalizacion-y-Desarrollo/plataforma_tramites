@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-neza-wine">Administración</p>
                <h1 class="mt-1 text-3xl font-bold text-neza-ink">Servicios y enlaces de interés</h1>
                <p class="mt-2 text-sm text-neza-muted">Administra los accesos que aparecen en la página principal.</p>
            </div>
            <a href="{{ route('servicios-interes.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Nuevo servicio
            </a>
        </div>

        <div class="overflow-hidden rounded-xl border border-neza-line bg-white shadow-sm">
            <div class="border-b border-neza-line px-5 py-4 text-sm font-semibold text-neza-ink">{{ $servicios->total() }} servicios registrados</div>
            <div class="divide-y divide-neza-line">
                @forelse ($servicios as $servicio)
                    <article class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
                        <img src="{{ asset('storage/'.$servicio->logo_storage_key) }}" alt="" class="size-14 shrink-0 rounded-lg border border-neza-line object-contain p-1" loading="lazy">
                        <div class="min-w-0 flex-1">
                            <h2 class="font-semibold text-neza-ink">{{ $servicio->titulo }}</h2>
                            <p class="mt-1 text-sm text-neza-muted">{{ $servicio->descripcion }}</p>
                            <a href="{{ $servicio->enlace }}" target="_blank" rel="noopener noreferrer" class="mt-2 block truncate text-xs text-neza-wine underline focus-visible:outline-2 focus-visible:outline-neza-wine">{{ $servicio->enlace }} <span class="sr-only">(abre en una pestaña nueva)</span></a>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <a href="{{ route('servicios-interes.edit', $servicio) }}" class="rounded-lg border border-neza-line px-3 py-2 text-sm font-semibold text-neza-wine hover:bg-neza-wine-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Editar</a>
                            <form action="{{ route('servicios-interes.destroy', $servicio) }}" method="POST" data-confirm-delete data-confirm-title="¿Eliminar servicio?" data-confirm-text="El enlace dejará de mostrarse en la página principal." data-confirm-button="Sí, eliminar">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-700">Eliminar</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="p-10 text-center">
                        <p class="font-semibold text-neza-ink">Aún no hay servicios</p>
                        <p class="mt-1 text-sm text-neza-muted">Agrega el primero para mostrarlo en la página principal.</p>
                    </div>
                @endforelse
            </div>
        </div>
        {{ $servicios->links() }}
    </div>
@endsection
