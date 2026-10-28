@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-[1440px] space-y-6">
        <section>
            <p class="text-sm font-semibold text-neza-wine">Solicitudes</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-neza-ink">Áreas operativas</h1>
            <p class="mt-2 text-sm text-neza-muted">Habilita los departamentos de Accesos que pueden administrar trámites en el portal.</p>
        </section>

        <section class="rounded-2xl border border-neza-line bg-white p-5 shadow-sm">
            <form method="POST" action="{{ route('solicitudes.areas.store') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                @csrf
                <div class="flex-1">
                    <label for="departamento_accesos_id" class="block text-sm font-semibold text-neza-ink">Departamento de Accesos</label>
                    <select id="departamento_accesos_id" name="departamento_accesos_id" required class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm text-neza-ink focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('departamento_accesos_id') border-rose-600 @enderror">
                        <option value="">Selecciona un departamento</option>
                        @foreach ($departamentos as $departamento)
                            <option value="{{ $departamento['id'] }}" @selected(old('departamento_accesos_id') === $departamento['id'])>{{ $departamento['nombre'] }}{{ $departamento['siglas'] ? ' · '.$departamento['siglas'] : '' }}</option>
                        @endforeach
                    </select>
                    @error('departamento_accesos_id')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <button type="submit" data-submit-lock class="inline-flex min-h-11 items-center justify-center rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Habilitar área</button>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-neza-line bg-white shadow-sm">
            <div class="overflow-x-auto"><table class="w-full min-w-[620px] text-left text-sm"><thead class="bg-neza-canvas text-xs uppercase tracking-wide text-neza-muted"><tr><th class="px-5 py-3">Área</th><th class="px-5 py-3">ID Accesos</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3 text-right">Acción</th></tr></thead><tbody class="divide-y divide-neza-line">
                @forelse ($areas as $area)
                    <tr><td class="px-5 py-4 font-semibold text-neza-ink">{{ $area->nombre_snapshot }}{{ $area->siglas_snapshot ? ' · '.$area->siglas_snapshot : '' }}</td><td class="px-5 py-4 text-neza-muted">{{ $area->accesos_departamento_id }}</td><td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $area->activa ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $area->activa ? 'Habilitada' : 'Deshabilitada' }}</span></td><td class="px-5 py-4 text-right"><form method="POST" action="{{ route('solicitudes.areas.update', $area) }}">@csrf @method('PATCH')<button type="submit" data-submit-lock class="font-semibold text-neza-wine hover:underline">{{ $area->activa ? 'Deshabilitar' : 'Habilitar' }}</button></form></td></tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-neza-muted">No hay áreas operativas configuradas.</td></tr>
                @endforelse
            </tbody></table></div>
        </section>
    </div>
@endsection
