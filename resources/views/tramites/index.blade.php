@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-[1440px] space-y-5">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-neza-wine">Administración</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-neza-ink">Catálogo de trámites</h1>
                <p class="mt-2 text-sm text-neza-muted">Gestiona los trámites disponibles en el sistema.</p>
            </div>
            <button type="button" data-modal-open="tramite-crear" class="inline-flex items-center justify-center gap-2 rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Nuevo trámite
            </button>
        </section>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_280px]">
            <section class="overflow-hidden rounded-xl border border-neza-line bg-white shadow-sm">
                <div class="flex flex-col gap-2 border-b border-neza-line px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-bold text-neza-ink">Trámites registrados</h2>
                        <p class="mt-1 text-xs text-neza-muted">Consulta las claves, descripciones y disponibilidad del catálogo.</p>
                    </div>
                    <span class="text-xs font-medium text-neza-muted">{{ $totalTramites }} en total</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[680px] text-left text-sm">
                        <thead class="bg-neza-canvas text-xs font-semibold text-neza-muted">
                            <tr>
                                <th class="px-5 py-3">Clave</th>
                                <th class="px-4 py-3">Nombre</th>
                                <th class="px-4 py-3">Descripción</th>
                                <th class="px-4 py-3">Detalles públicos</th>
                                <th class="px-5 py-3 text-right">Estado</th>
                                <th class="px-5 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neza-line">
                            @forelse ($tramites as $tramite)
                                <tr class="hover:bg-neza-canvas">
                                    <td class="px-5 py-4 font-mono text-xs font-semibold text-neza-wine">{{ $tramite->clave }}</td>
                                    <td class="px-4 py-4 font-semibold text-neza-ink">{{ $tramite->nombre }}</td>
                                    <td class="max-w-xl px-4 py-4 text-neza-muted">{{ $tramite->descripcion ?: 'Sin descripción' }}</td>
                                    <td class="px-4 py-4 text-xs text-neza-muted"><span class="block">{{ $tramite->duracion ?: 'Sin duración' }}</span><span class="block">{{ $tramite->costo ?: 'Sin costo' }} · {{ $tramite->tiempo_estimado ?: 'Sin tiempo estimado' }}</span><span class="block">{{ collect($tramite->modalidades ?? [])->map(fn ($modalidad) => $modalidad === 'EN_LINEA' ? 'En línea' : 'Presencial')->join(' y ') ?: 'Sin modalidad' }}</span></td>
                                    <td class="px-5 py-4 text-right">
                                        <span @class([
                                            'rounded-full px-2.5 py-1 text-xs font-semibold',
                                            'bg-emerald-50 text-emerald-700' => $tramite->activo,
                                            'bg-slate-100 text-slate-700' => ! $tramite->activo,
                                        ])>{{ $tramite->activo ? 'Activo' : 'Inactivo' }}</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-3">
                                            @if (false && $tramite->estado?->clave === 'BORRADOR')
                                                <form action="{{ route('catalogo-tramites.publicar', $tramite) }}" method="POST" data-confirm-action data-confirm-title="¿Publicar trámite?" data-confirm-text="El trámite estará visible al público si también se encuentra activo." data-confirm-button="Sí, publicar" class="inline-flex">@csrf<button type="submit" title="Publicar trámite" aria-label="Publicar trámite" class="text-emerald-700 transition hover:text-emerald-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700"><svg class="size-6" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg></button></form>
                                            @elseif (false && $tramite->estado?->clave === 'PUBLICADO')
                                                <form action="{{ route('catalogo-tramites.archivar', $tramite) }}" method="POST" data-confirm-action data-confirm-title="¿Archivar trámite?" data-confirm-text="El trámite dejará de aparecer en las vistas públicas." data-confirm-button="Sí, archivar" class="inline-flex">@csrf<button type="submit" title="Archivar trámite" aria-label="Archivar trámite" class="text-slate-700 transition hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-700"><svg class="size-6" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" /></svg></button></form>
                                            @endif
                                            <form action="{{ route('catalogo-tramites.estado', $tramite) }}" method="POST" data-confirm-action data-confirm-title="{{ $tramite->activo ? '¿Deshabilitar trámite?' : '¿Activar trámite?' }}" data-confirm-text="{{ $tramite->activo ? 'El trámite dejará de estar disponible para nuevas configuraciones.' : 'El trámite estará disponible nuevamente para nuevas configuraciones.' }}" data-confirm-button="Sí, continuar" class="inline-flex">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" title="{{ $tramite->activo ? 'Deshabilitar trámite' : 'Activar trámite' }}" aria-label="{{ $tramite->activo ? 'Deshabilitar trámite' : 'Activar trámite' }}" @class([
                                                    'transition focus-visible:outline-2 focus-visible:outline-offset-2',
                                                    'text-emerald-700 hover:text-emerald-900 focus-visible:outline-emerald-700' => $tramite->activo,
                                                    'text-slate-500 hover:text-neza-wine focus-visible:outline-neza-wine' => ! $tramite->activo,
                                                ])>
                                                    <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2.25v9m6.364-4.114a9 9 0 1 1-12.728 0" /></svg>
                                                </button>
                                            </form>
                                            <button type="button" title="Editar trámite" aria-label="Editar trámite" data-modal-open="tramite-editar" data-tramite-id="{{ $tramite->id }}" data-tramite-clave="{{ $tramite->clave }}" data-tramite-nombre="{{ $tramite->nombre }}" data-tramite-descripcion="{{ $tramite->descripcion }}" data-tramite-duracion="{{ $tramite->duracion }}" data-tramite-costo="{{ $tramite->costo }}" data-tramite-tiempo-estimado="{{ $tramite->tiempo_estimado }}" data-tramite-modalidades='@json($tramite->modalidades ?? [])' data-tramite-requisitos='@json($tramite->requisitos ?? [])' data-tramite-proceso='@json($tramite->proceso ?? [])' data-tramite-preguntas-frecuentes='@json($tramite->preguntas_frecuentes ?? [])' data-tramite-activo="{{ $tramite->activo ? '1' : '0' }}" data-tramite-update-url="{{ route('catalogo-tramites.update', $tramite) }}" class="text-neza-muted transition hover:text-neza-wine focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                                                <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                            </button>
                                            <form action="{{ route('catalogo-tramites.destroy', $tramite) }}" method="POST" data-confirm-delete data-confirm-title="¿Eliminar trámite?" data-confirm-text="El trámite se ocultará del catálogo, pero su información histórica se conservará." class="inline-flex">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Eliminar trámite" aria-label="Eliminar trámite" class="text-neza-muted transition hover:text-rose-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-700">
                                                    <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-16 text-center">
                                        <div class="mx-auto flex max-w-sm flex-col items-center">
                                            <span class="grid size-12 place-items-center rounded-full bg-neza-wine-soft text-neza-wine">
                                                <svg class="size-6" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5V6.75a3.375 3.375 0 0 0-3.375-3.375h-3.75A3.375 3.375 0 0 0 4.125 6.75v10.5a3.375 3.375 0 0 0 3.375 3.375H12" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 14.25h3.75m0 0V18m0-3.75-4.5 4.5" /></svg>
                                            </span>
                                            <h3 class="mt-4 font-semibold text-neza-ink">Aún no hay trámites</h3>
                                            <p class="mt-1 text-sm text-neza-muted">Crea el primer trámite para habilitar la configuración de sus formularios.</p>
                                            <button type="button" data-modal-open="tramite-crear" class="mt-4 text-sm font-semibold text-neza-wine hover:underline">Crear trámite</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($tramites->hasPages())
                    <div class="flex items-center justify-between border-t border-neza-line px-5 py-4 text-xs text-neza-muted">
                        <span>Mostrando {{ $tramites->firstItem() }} a {{ $tramites->lastItem() }} de {{ $tramites->total() }} resultados</span>
                        <div class="flex gap-2">
                            @if ($tramites->onFirstPage())
                                <span class="rounded-md border border-neza-line px-2 py-1 opacity-50">Anterior</span>
                            @else
                                <a href="{{ $tramites->previousPageUrl() }}" class="rounded-md border border-neza-line px-2 py-1 hover:bg-neza-canvas">Anterior</a>
                            @endif
                            @if ($tramites->hasMorePages())
                                <a href="{{ $tramites->nextPageUrl() }}" class="rounded-md border border-neza-line px-2 py-1 hover:bg-neza-canvas">Siguiente</a>
                            @else
                                <span class="rounded-md border border-neza-line px-2 py-1 opacity-50">Siguiente</span>
                            @endif
                        </div>
                    </div>
                @endif
            </section>

            <aside class="space-y-5">
                <section class="rounded-xl border border-neza-line bg-white p-5 shadow-sm">
                    <h2 class="text-base font-bold text-neza-ink">Resumen rápido</h2>
                    <dl class="mt-4 divide-y divide-neza-line">
                        <div class="flex items-center justify-between py-3"><dt class="text-sm text-neza-muted">Activos</dt><dd class="text-lg font-bold text-emerald-700">{{ $tramitesActivos }}</dd></div>
                        <div class="flex items-center justify-between py-3"><dt class="text-sm text-neza-muted">Inactivos</dt><dd class="text-lg font-bold text-slate-600">{{ $tramitesInactivos }}</dd></div>
                        <div class="flex items-center justify-between py-3"><dt class="text-sm text-neza-muted">Total</dt><dd class="text-lg font-bold text-neza-wine">{{ $totalTramites }}</dd></div>
                    </dl>
                </section>
            </aside>
        </div>

        <dialog id="tramite-crear" @if ($errors->any() && old('_modal') === 'crear') open @endif class="fixed inset-0 m-auto w-full max-w-xl rounded-xl border border-neza-line p-0 shadow-xl backdrop:bg-neza-ink/40">
            <form action="{{ route('catalogo-tramites.store') }}" method="POST" class="bg-white" novalidate>
                @csrf
                <input type="hidden" name="_modal" value="crear">
                <div class="flex items-start justify-between border-b border-neza-line px-6 py-5"><div><h2 class="text-lg font-bold text-neza-ink">Nuevo trámite</h2><p class="mt-1 text-sm text-neza-muted">Registra la información del catálogo.</p></div><button type="button" data-modal-close="tramite-crear" class="text-neza-muted hover:text-neza-ink" aria-label="Cerrar"><svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button></div>
                <div class="space-y-5 p-6">
                    <div><label for="crear-clave" class="block text-sm font-semibold text-neza-ink">Clave</label><input id="crear-clave" value="Se genera automáticamente al guardar" disabled class="mt-2 w-full cursor-not-allowed rounded-lg border border-neza-line bg-neza-canvas px-3 py-2.5 text-sm text-neza-muted"></div>
                    <div><label for="crear-nombre" class="block text-sm font-semibold text-neza-ink">Nombre <span class="text-rose-700">*</span></label><input id="crear-nombre" name="nombre" value="{{ old('_modal') === 'crear' ? old('nombre') : '' }}" required maxlength="200" class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm text-neza-ink @error('nombre') border-rose-600 @enderror">@if (old('_modal') === 'crear') @error('nombre')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror @endif</div>
                    <div><label for="crear-descripcion" class="block text-sm font-semibold text-neza-ink">Descripción</label><textarea id="crear-descripcion" name="descripcion" rows="4" maxlength="5000" class="mt-2 w-full resize-y rounded-lg border border-neza-line px-3 py-2.5 text-sm text-neza-ink @error('descripcion') border-rose-600 @enderror">{{ old('_modal') === 'crear' ? old('descripcion') : '' }}</textarea>@if (old('_modal') === 'crear') @error('descripcion')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror @endif</div>
                    @include('tramites.partials.datos-publicos', ['modo' => 'crear', 'tramite' => null])
                    <label class="flex items-center gap-3 text-sm font-semibold text-neza-ink"><input type="checkbox" name="activo" value="1" @checked(old('_modal') !== 'crear' || old('activo')) class="size-4 rounded border-neza-line text-neza-wine focus:ring-neza-wine"> Trámite activo</label>
                </div>
                <div class="flex justify-end gap-3 border-t border-neza-line px-6 py-4"><button type="button" data-modal-close="tramite-crear" class="rounded-lg border border-neza-line px-4 py-2.5 text-sm font-semibold text-neza-muted hover:bg-neza-canvas">Cancelar</button><button type="submit" class="rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Crear trámite</button></div>
            </form>
        </dialog>

        <dialog id="tramite-editar" @if ($errors->any() && old('_modal') === 'editar') open @endif class="fixed inset-0 m-auto w-full max-w-xl rounded-xl border border-neza-line p-0 shadow-xl backdrop:bg-neza-ink/40">
            <form id="tramite-editar-form" action="{{ old('update_url', '') }}" method="POST" class="bg-white" novalidate>
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="editar"><input type="hidden" name="update_url" value="{{ old('update_url') }}"><input type="hidden" name="clave_visual" value="{{ old('clave_visual') }}">
                <div class="flex items-start justify-between border-b border-neza-line px-6 py-5"><div><h2 class="text-lg font-bold text-neza-ink">Editar trámite</h2><p class="mt-1 text-sm text-neza-muted">Actualiza los datos del trámite.</p></div><button type="button" data-modal-close="tramite-editar" class="text-neza-muted hover:text-neza-ink" aria-label="Cerrar"><svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button></div>
                <div class="space-y-5 p-6">
                    <div><label for="editar-clave" class="block text-sm font-semibold text-neza-ink">Clave</label><input id="editar-clave" value="{{ old('clave_visual') }}" disabled class="mt-2 w-full cursor-not-allowed rounded-lg border border-neza-line bg-neza-canvas px-3 py-2.5 text-sm text-neza-muted"></div>
                    <div><label for="editar-nombre" class="block text-sm font-semibold text-neza-ink">Nombre <span class="text-rose-700">*</span></label><input id="editar-nombre" name="nombre" value="{{ old('_modal') === 'editar' ? old('nombre') : '' }}" required maxlength="200" class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm text-neza-ink @error('nombre') border-rose-600 @enderror">@if (old('_modal') === 'editar') @error('nombre')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror @endif</div>
                    <div><label for="editar-descripcion" class="block text-sm font-semibold text-neza-ink">Descripción</label><textarea id="editar-descripcion" name="descripcion" rows="4" maxlength="5000" class="mt-2 w-full resize-y rounded-lg border border-neza-line px-3 py-2.5 text-sm text-neza-ink @error('descripcion') border-rose-600 @enderror">{{ old('_modal') === 'editar' ? old('descripcion') : '' }}</textarea>@if (old('_modal') === 'editar') @error('descripcion')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror @endif</div>
                    @include('tramites.partials.datos-publicos', ['modo' => 'editar', 'tramite' => null])
                    <label class="flex items-center gap-3 text-sm font-semibold text-neza-ink"><input id="editar-activo" type="checkbox" name="activo" value="1" @checked(old('_modal') === 'editar' && old('activo')) class="size-4 rounded border-neza-line text-neza-wine focus:ring-neza-wine"> Trámite activo</label>
                </div>
                <div class="flex justify-end gap-3 border-t border-neza-line px-6 py-4"><button type="button" data-modal-close="tramite-editar" class="rounded-lg border border-neza-line px-4 py-2.5 text-sm font-semibold text-neza-muted hover:bg-neza-canvas">Cancelar</button><button type="submit" class="rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Guardar cambios</button></div>
            </form>
        </dialog>
    </div>
@endsection
