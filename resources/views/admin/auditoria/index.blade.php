@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-neza-wine">Administración</p>
                <h1 class="mt-1 text-3xl font-bold text-neza-ink">Auditoría</h1>
                <p class="mt-2 text-sm text-neza-muted">Registro inmutable de las acciones realizadas por el equipo interno.</p>
            </div>
            <span class="w-fit rounded-full bg-neza-wine-soft px-3 py-1 text-xs font-bold text-neza-wine">{{ $auditorias->total() }} registros</span>
        </div>

        <form method="GET" action="{{ route('auditoria.index') }}" class="grid gap-4 rounded-xl border border-neza-line bg-white p-5 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="actor" class="block text-xs font-semibold text-neza-ink">Actor</label>
                <input id="actor" name="actor" type="text" value="{{ $filtros['actor'] ?? '' }}" placeholder="Nombre o ID" class="mt-1 block w-full rounded-lg border border-neza-line bg-white px-3 py-2 text-sm text-neza-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                @error('actor')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="accion" class="block text-xs font-semibold text-neza-ink">Acción</label>
                <select id="accion" name="accion" class="mt-1 block w-full rounded-lg border border-neza-line bg-white px-3 py-2 text-sm text-neza-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                    <option value="">Todas</option>
                    @foreach ($acciones as $accion)
                        <option value="{{ $accion }}" @selected(($filtros['accion'] ?? null) === $accion)>{{ $accion }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="entidad" class="block text-xs font-semibold text-neza-ink">Entidad</label>
                <select id="entidad" name="entidad" class="mt-1 block w-full rounded-lg border border-neza-line bg-white px-3 py-2 text-sm text-neza-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                    <option value="">Todas</option>
                    @foreach ($entidades as $entidad)
                        <option value="{{ $entidad }}" @selected(($filtros['entidad'] ?? null) === $entidad)>{{ $entidad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="desde" class="block text-xs font-semibold text-neza-ink">Desde</label>
                <input id="desde" name="desde" type="date" value="{{ $filtros['desde'] ?? '' }}" class="mt-1 block w-full rounded-lg border border-neza-line bg-white px-3 py-2 text-sm text-neza-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                @error('desde')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="hasta" class="block text-xs font-semibold text-neza-ink">Hasta</label>
                <input id="hasta" name="hasta" type="date" value="{{ $filtros['hasta'] ?? '' }}" class="mt-1 block w-full rounded-lg border border-neza-line bg-white px-3 py-2 text-sm text-neza-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                @error('hasta')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-5">
                <button type="submit" class="rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Filtrar</button>
                <a href="{{ route('auditoria.index') }}" class="rounded-lg border border-neza-line px-4 py-2.5 text-sm font-semibold text-neza-wine hover:bg-neza-wine-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Limpiar</a>
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border border-neza-line bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neza-line text-sm">
                    <thead class="bg-neza-canvas text-left text-xs font-bold uppercase tracking-wide text-neza-muted">
                        <tr>
                            <th scope="col" class="px-5 py-3">Fecha</th>
                            <th scope="col" class="px-5 py-3">Actor</th>
                            <th scope="col" class="px-5 py-3">Acción</th>
                            <th scope="col" class="px-5 py-3">Entidad</th>
                            <th scope="col" class="px-5 py-3">Detalle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neza-line">
                        @forelse ($auditorias as $auditoria)
                            <tr class="align-top">
                                <td class="whitespace-nowrap px-5 py-4 text-neza-muted">{{ $auditoria->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-neza-ink">{{ $auditoria->actor_nombre_snapshot ?: 'Sin actor' }}</p>
                                    <p class="mt-1 text-xs text-neza-muted">{{ $auditoria->actor_departamento_snapshot ?: 'Sin área' }}</p>
                                    <p class="mt-1 text-xs text-neza-muted">ID: {{ $auditoria->actor_accesos_id ?: '—' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="w-fit rounded-full bg-neza-wine-soft px-2.5 py-1 text-xs font-bold text-neza-wine">{{ $auditoria->accion }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-neza-ink">{{ $auditoria->entidad }}</p>
                                    <p class="mt-1 text-xs text-neza-muted">{{ $auditoria->entidad_id ?: '—' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-xs text-neza-muted">IP: {{ $auditoria->ip ?: '—' }}</p>
                                    @if ($auditoria->datos_anteriores || $auditoria->datos_nuevos)
                                        <details class="mt-2">
                                            <summary class="cursor-pointer list-none text-xs font-semibold text-neza-wine hover:underline [&::-webkit-details-marker]:hidden">Ver cambios</summary>
                                            <div class="mt-2 space-y-2">
                                                @if ($auditoria->datos_anteriores)
                                                    <div>
                                                        <p class="text-xs font-semibold text-neza-muted">Antes</p>
                                                        <pre class="mt-1 max-h-56 overflow-auto whitespace-pre-wrap break-words rounded-lg bg-neza-canvas p-3 text-xs text-neza-ink">{{ json_encode($auditoria->datos_anteriores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                                    </div>
                                                @endif
                                                @if ($auditoria->datos_nuevos)
                                                    <div>
                                                        <p class="text-xs font-semibold text-neza-muted">Después</p>
                                                        <pre class="mt-1 max-h-56 overflow-auto whitespace-pre-wrap break-words rounded-lg bg-neza-canvas p-3 text-xs text-neza-ink">{{ json_encode($auditoria->datos_nuevos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                                    </div>
                                                @endif
                                            </div>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center">
                                    <p class="font-semibold text-neza-ink">No hay registros de auditoría</p>
                                    <p class="mt-1 text-sm text-neza-muted">Ajusta los filtros o realiza una acción en el sistema para comenzar.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $auditorias->links() }}
    </div>
@endsection
