@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-[1440px] space-y-5">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-neza-wine">Administración</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-neza-ink">Catálogo de estados de solicitudes</h1>
                <p class="mt-2 text-sm text-neza-muted">Gestiona los estados disponibles para las solicitudes.</p>
            </div>
            <button type="button" data-modal-open="estado-crear"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo estado
            </button>
        </section>
        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_280px]">
            <section class="overflow-hidden rounded-xl border border-neza-line bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-neza-line px-5 py-4">
                    <div>
                        <h2 class="text-base font-bold text-neza-ink">Estados registrados</h2>
                        <p class="mt-1 text-xs text-neza-muted">Configura los estados y sus reglas de finalización.</p>
                    </div><span class="text-xs font-medium text-neza-muted">{{ $totalEstados }} en total</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[880px] text-left text-sm">
                        <thead class="bg-neza-canvas text-xs font-semibold text-neza-muted">
                            <tr>
                                <th class="px-5 py-3">Clave</th>
                                <th class="px-4 py-3">Nombre</th>
                                <th class="px-4 py-3">Descripción</th>
                                <th class="px-4 py-3 text-center">Orden</th>
                                <th class="px-4 py-3">Final</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-5 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neza-line">
                            @forelse ($estados as $estado)
                                <tr class="hover:bg-neza-canvas">
                                    <td class="px-5 py-4 font-mono text-xs font-semibold text-neza-wine">
                                        {{ $estado->clave }}</td>
                                    <td class="px-4 py-4 font-semibold text-neza-ink">{{ $estado->nombre }}</td>
                                    <td class="max-w-sm px-4 py-4 text-neza-muted">
                                        {{ $estado->descripcion ?: 'Sin descripción' }}</td>
                                    <td class="px-4 py-4 text-center text-neza-muted">{{ $estado->orden }}</td>
                                    <td class="px-4 py-4"><span
                                            class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $estado->es_final ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-700' }}">{{ $estado->es_final ? 'Sí' : 'No' }}</span>
                                    </td>
                                    <td class="px-4 py-4"><span
                                            class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $estado->activo ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">{{ $estado->activo ? 'Activo' : 'Inactivo' }}</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-3">
                                            <form action="{{ route('catalogo-estados.estado', $estado) }}" method="POST"
                                                data-confirm-action
                                                data-confirm-title="{{ $estado->activo ? '¿Deshabilitar estado?' : '¿Activar estado?' }}"
                                                data-confirm-text="{{ $estado->activo ? 'El estado dejará de estar disponible para nuevas solicitudes.' : 'El estado estará disponible nuevamente.' }}"
                                                data-confirm-button="Sí, continuar" class="inline-flex">@csrf
                                                @method('PATCH')<button type="submit"
                                                    title="{{ $estado->activo ? 'Deshabilitar estado' : 'Activar estado' }}"
                                                    class="text-emerald-700 hover:text-emerald-900"><svg class="size-5"
                                                        aria-hidden="true" fill="none" viewBox="0 0 24 24"
                                                        stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M12 2.25v9m6.364-4.114a9 9 0 1 1-12.728 0" />
                                                    </svg></button></form>
                                            <button type="button" title="Editar estado" data-modal-open="estado-editar"
                                                data-estado-clave="{{ $estado->clave }}"
                                                data-estado-nombre="{{ $estado->nombre }}"
                                                data-estado-descripcion="{{ $estado->descripcion }}"
                                                data-estado-orden="{{ $estado->orden }}"
                                                data-estado-final="{{ $estado->es_final ? '1' : '0' }}"
                                                data-estado-activo="{{ $estado->activo ? '1' : '0' }}"
                                                data-estado-update-url="{{ route('catalogo-estados.update', $estado) }}"
                                                class="text-neza-muted hover:text-neza-wine"><svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></button>
                                            <form action="{{ route('catalogo-estados.destroy', $estado) }}" method="POST"
                                                data-confirm-delete data-confirm-title="¿Eliminar estado?"
                                                data-confirm-text="Se eliminará lógicamente si no tiene solicitudes ni historial relacionados."
                                                class="inline-flex">@csrf @method('DELETE')<button type="submit"
                                                    title="Eliminar estado" class="text-neza-muted hover:text-rose-700"> <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg></button></form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-16 text-center text-sm text-neza-muted">No hay estados
                                        de solicitud registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            <aside>
                <section class="rounded-xl border border-neza-line bg-white p-5 shadow-sm">
                    <h2 class="text-base font-bold text-neza-ink">Resumen rápido</h2>
                    <dl class="mt-4 divide-y divide-neza-line">
                        <div class="flex items-center justify-between py-3">
                            <dt class="text-sm text-neza-muted">Activos</dt>
                            <dd class="text-lg font-bold text-emerald-700">{{ $estadosActivos }}</dd>
                        </div>
                        <div class="flex items-center justify-between py-3">
                            <dt class="text-sm text-neza-muted">Finales</dt>
                            <dd class="text-lg font-bold text-amber-700">{{ $estadosFinales }}</dd>
                        </div>
                        <div class="flex items-center justify-between py-3">
                            <dt class="text-sm text-neza-muted">Total</dt>
                            <dd class="text-lg font-bold text-neza-wine">{{ $totalEstados }}</dd>
                        </div>
                    </dl>
                </section>
            </aside>
        </div>
        @include('catalogos.solicitud_estado.modales')
    </div>
@endsection
