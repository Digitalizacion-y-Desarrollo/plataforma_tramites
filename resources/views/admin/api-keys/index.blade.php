@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div>
            <p class="text-sm font-semibold text-neza-wine">Configuración</p>
            <h1 class="mt-1 text-3xl font-bold text-neza-ink">API Keys</h1>
            <p class="mt-2 max-w-3xl text-sm text-neza-muted">Crea credenciales para que sistemas externos consulten el listado de solicitudes mediante la API.</p>
        </div>

        <section class="rounded-xl border border-neza-line bg-white p-5 shadow-sm sm:p-6" aria-labelledby="crear-api-key">
            <h2 id="crear-api-key" class="text-lg font-bold text-neza-ink">Nueva API Key</h2>
            <form method="POST" action="{{ route('api-keys.store') }}" class="mt-5 grid gap-5 md:grid-cols-2" data-submit-lock>
                @csrf
                <div>
                    <label for="nombre" class="block text-sm font-semibold text-neza-ink">Nombre o sistema <span class="text-rose-700">*</span></label>
                    <input id="nombre" name="nombre" value="{{ old('nombre') }}" maxlength="150" required class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('nombre') border-rose-600 @enderror" placeholder="Ej. Sistema de reportes">
                    @error('nombre')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="expira_en" class="block text-sm font-semibold text-neza-ink">Fecha de expiración <span class="font-normal text-neza-muted">(opcional)</span></label>
                    <input id="expira_en" name="expira_en" type="date" value="{{ old('expira_en') }}" min="{{ now()->addDay()->toDateString() }}" class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('expira_en') border-rose-600 @enderror">
                    @error('expira_en')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2 flex flex-wrap items-center justify-between gap-4 border-t border-neza-line pt-5">
                    <p class="text-xs text-neza-muted">La clave se mostrará solo una vez. Guárdala en el sistema consumidor antes de cerrar el aviso.</p>
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Generar API Key</button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border border-neza-line bg-white shadow-sm" aria-labelledby="api-keys-registradas">
            <div class="border-b border-neza-line px-5 py-4">
                <h2 id="api-keys-registradas" class="font-semibold text-neza-ink">{{ $apiKeys->total() }} API Keys registradas</h2>
            </div>
            <div class="divide-y divide-neza-line">
                @forelse ($apiKeys as $apiKey)
                    <article class="flex flex-col gap-4 p-5 lg:flex-row lg:items-center">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-semibold text-neza-ink">{{ $apiKey->nombre }}</h3>
                                <span @class(['rounded-full px-2.5 py-1 text-xs font-bold', 'bg-emerald-50 text-emerald-700' => $apiKey->activo && (! $apiKey->expira_en || $apiKey->expira_en->isFuture()), 'bg-rose-50 text-rose-700' => ! $apiKey->activo || ($apiKey->expira_en && $apiKey->expira_en->isPast())])>{{ ! $apiKey->activo ? 'Revocada' : (($apiKey->expira_en && $apiKey->expira_en->isPast()) ? 'Expirada' : 'Activa') }}</span>
                            </div>
                            <p class="mt-2 font-mono text-sm text-neza-muted">{{ $apiKey->prefijo }}…</p>
                            <dl class="mt-3 grid gap-x-6 gap-y-1 text-xs text-neza-muted sm:grid-cols-3">
                                <div><dt class="inline font-semibold text-neza-ink">Creada:</dt> <dd class="inline">{{ $apiKey->created_at->format('Y-m-d H:i') }}</dd></div>
                                <div><dt class="inline font-semibold text-neza-ink">Último uso:</dt> <dd class="inline">{{ $apiKey->ultimo_uso_at?->format('Y-m-d H:i') ?? 'Sin uso' }}</dd></div>
                                <div><dt class="inline font-semibold text-neza-ink">Expira:</dt> <dd class="inline">{{ $apiKey->expira_en?->format('Y-m-d') ?? 'No expira' }}</dd></div>
                            </dl>
                        </div>
                        @if ($apiKey->activo)
                            <form method="POST" action="{{ route('api-keys.revocar', $apiKey) }}" data-confirm-action data-confirm-title="¿Revocar API Key?" data-confirm-text="El sistema externo dejará de poder consultar la API de inmediato." data-confirm-button="Sí, revocar" data-submit-lock>
                                @csrf
                                <button type="submit" class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-700">Revocar</button>
                            </form>
                        @endif
                    </article>
                @empty
                    <div class="p-10 text-center">
                        <p class="font-semibold text-neza-ink">Aún no hay API Keys</p>
                        <p class="mt-1 text-sm text-neza-muted">Genera una clave para conectar el primer sistema externo.</p>
                    </div>
                @endforelse
            </div>
        </section>
        {{ $apiKeys->links() }}

        <section class="rounded-xl border border-neza-line bg-white p-5 shadow-sm sm:p-6" aria-labelledby="documentacion-api">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-neza-wine">Integración</p>
                    <h2 id="documentacion-api" class="mt-1 text-xl font-bold text-neza-ink">Cómo consultar las solicitudes</h2>
                    <p class="mt-2 text-sm text-neza-muted">La API es de solo lectura. Usa una API Key activa en el encabezado <code class="rounded bg-neza-canvas px-1.5 py-0.5 font-mono text-xs text-neza-ink">Authorization</code>.</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">GET · Solo lectura</span>
            </div>

            <div class="mt-6 grid gap-5 lg:grid-cols-2">
                <div class="rounded-lg border border-neza-line bg-neza-canvas p-4">
                    <h3 class="text-sm font-bold text-neza-ink">Endpoint</h3>
                    <code class="mt-3 block break-all rounded-md border border-neza-line bg-white px-3 py-2 font-mono text-xs text-neza-ink">GET {{ url('/api/v1/solicitudes') }}</code>
                    <h3 class="mt-5 text-sm font-bold text-neza-ink">Encabezados requeridos</h3>
                    <pre class="mt-3 overflow-x-auto rounded-md border border-neza-line bg-neza-ink p-3 text-xs leading-6 text-white"><code>Authorization: Bearer pt_live_tu_api_key
Accept: application/json</code></pre>
                </div>

                <div class="rounded-lg border border-neza-line bg-neza-canvas p-4">
                    <h3 class="text-sm font-bold text-neza-ink">Ejemplo con cURL</h3>
                    <pre class="mt-3 overflow-x-auto rounded-md border border-neza-line bg-neza-ink p-3 text-xs leading-6 text-white"><code>curl -X GET "{{ url('/api/v1/solicitudes') }}?per_page=20" \
  -H "Authorization: Bearer pt_live_tu_api_key" \
  -H "Accept: application/json"</code></pre>
                    <p class="mt-3 text-xs text-neza-muted">En Postman, selecciona <strong>Authorization → Bearer Token</strong> y pega la API Key generada.</p>
                </div>
            </div>

            <div class="mt-6">
                <h3 class="text-sm font-bold text-neza-ink">Filtros opcionales</h3>
                <div class="mt-3 overflow-x-auto rounded-lg border border-neza-line">
                    <table class="min-w-full divide-y divide-neza-line text-left text-sm">
                        <thead class="bg-neza-canvas text-xs font-bold uppercase tracking-wide text-neza-muted"><tr><th class="px-4 py-3">Parámetro</th><th class="px-4 py-3">Descripción</th><th class="px-4 py-3">Ejemplo</th></tr></thead>
                        <tbody class="divide-y divide-neza-line bg-white text-neza-ink">
                            <tr><td class="px-4 py-3 font-mono text-xs">area_id</td><td class="px-4 py-3">ID del departamento de Accesos.</td><td class="px-4 py-3 font-mono text-xs">area_id=1</td></tr>
                            <tr><td class="px-4 py-3 font-mono text-xs">estado_id</td><td class="px-4 py-3">ID del estado de la solicitud.</td><td class="px-4 py-3 font-mono text-xs">estado_id=2</td></tr>
                            <tr><td class="px-4 py-3 font-mono text-xs">tramite_id</td><td class="px-4 py-3">ID del trámite.</td><td class="px-4 py-3 font-mono text-xs">tramite_id=10</td></tr>
                            <tr><td class="px-4 py-3 font-mono text-xs">per_page</td><td class="px-4 py-3">Registros por página. Mínimo 1, máximo 100.</td><td class="px-4 py-3 font-mono text-xs">per_page=50</td></tr>
                            <tr><td class="px-4 py-3 font-mono text-xs">page</td><td class="px-4 py-3">Número de página.</td><td class="px-4 py-3 font-mono text-xs">page=2</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-neza-muted">Ejemplo combinado: <code class="rounded bg-neza-canvas px-1.5 py-0.5 font-mono text-xs text-neza-ink">?area_id=1&amp;estado_id=2&amp;tramite_id=10&amp;per_page=50</code></p>
            </div>

            <div class="mt-6 rounded-lg border border-neza-line bg-neza-canvas p-4">
                <h3 class="text-sm font-bold text-neza-ink">Respuesta</h3>
                <p class="mt-1 text-xs text-neza-muted">La respuesta contiene <code class="font-mono">data</code> con las solicitudes y <code class="font-mono">links</code> y <code class="font-mono">meta</code> para la paginación.</p>
                <pre class="mt-3 overflow-x-auto rounded-md border border-neza-line bg-neza-ink p-3 text-xs leading-6 text-white"><code>{
  "data": [
    {
      "folio": "NEZA-000001",
      "area": { "id": "1", "nombre": "DIRECCIÓN DE DESARROLLO URBANO" },
      "tramite": { "id": 10, "clave": "LIC_UNICA_CONSTRUCCION" },
      "estado": { "id": 2, "clave": "ENVIADA" }
    }
  ],
  "links": { "next": null },
  "meta": { "current_page": 1, "per_page": 20, "total": 1 }
}</code></pre>
            </div>

            <p class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-900"><strong>Seguridad:</strong> la API Key equivale a una credencial de acceso. No la compartas por correo ni la publiques en código fuente. Si se compromete, revócala aquí y genera una nueva.</p>
        </section>
    </div>

    @if (session('api_key_generada'))
        <span hidden data-api-key-generada data-api-key="{{ session('api_key_generada') }}"></span>
    @endif
@endsection
