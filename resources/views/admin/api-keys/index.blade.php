@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div>
            <p class="text-sm font-semibold text-neza-wine">Configuración</p>
            <h1 class="mt-1 text-3xl font-bold text-neza-ink">API Keys</h1>
            <p class="mt-2 max-w-3xl text-sm text-neza-muted">Crea credenciales para que sistemas externos consulten solicitudes y el catálogo de trámites mediante la API.</p>
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

        <section class="rounded-xl border border-neza-line bg-white p-5 shadow-sm sm:p-6" aria-labelledby="documentacion-api-tramites">
            <div><p class="text-sm font-semibold text-neza-wine">Integración</p><h2 id="documentacion-api-tramites" class="mt-1 text-xl font-bold text-neza-ink">Cómo consultar el catálogo de trámites</h2><p class="mt-2 text-sm text-neza-muted">Incluye borradores, publicados y archivados. Usa la misma API Key activa mediante Bearer Token.</p></div>
            <div class="mt-6 grid gap-5 lg:grid-cols-2"><div class="rounded-lg border border-neza-line bg-neza-canvas p-4"><h3 class="text-sm font-bold text-neza-ink">Endpoint</h3><code class="mt-3 block break-all rounded-md border border-neza-line bg-white px-3 py-2 font-mono text-xs text-neza-ink">GET {{ url('/api/v1/tramites') }}</code><h3 class="mt-5 text-sm font-bold text-neza-ink">Encabezados requeridos</h3><pre class="mt-3 overflow-x-auto rounded-md border border-neza-line bg-neza-ink p-3 text-xs leading-6 text-white"><code>Authorization: Bearer pt_live_tu_api_key
Accept: application/json</code></pre></div><div class="rounded-lg border border-neza-line bg-neza-canvas p-4"><h3 class="text-sm font-bold text-neza-ink">Ejemplo con cURL</h3><pre class="mt-3 overflow-x-auto rounded-md border border-neza-line bg-neza-ink p-3 text-xs leading-6 text-white"><code>curl -X GET "{{ url('/api/v1/tramites') }}?estado=PUBLICADO&amp;per_page=20" \
  -H "Authorization: Bearer pt_live_tu_api_key" \
  -H "Accept: application/json"</code></pre></div></div>
            <div class="mt-6 overflow-x-auto rounded-lg border border-neza-line"><table class="min-w-full divide-y divide-neza-line text-left text-sm"><thead class="bg-neza-canvas text-xs font-bold uppercase tracking-wide text-neza-muted"><tr><th class="px-4 py-3">Parámetro</th><th class="px-4 py-3">Descripción</th></tr></thead><tbody class="divide-y divide-neza-line bg-white text-neza-ink"><tr><td class="px-4 py-3 font-mono text-xs">q</td><td class="px-4 py-3">Busca por clave, nombre o descripción.</td></tr><tr><td class="px-4 py-3 font-mono text-xs">departamento_accesos_id</td><td class="px-4 py-3">ID del departamento responsable.</td></tr><tr><td class="px-4 py-3 font-mono text-xs">estado</td><td class="px-4 py-3">BORRADOR, PUBLICADO o ARCHIVADO.</td></tr><tr><td class="px-4 py-3 font-mono text-xs">activo</td><td class="px-4 py-3">1 para habilitados o 0 para deshabilitados.</td></tr><tr><td class="px-4 py-3 font-mono text-xs">modalidad</td><td class="px-4 py-3">EN_LINEA, PRESENCIAL o MIXTO.</td></tr><tr><td class="px-4 py-3 font-mono text-xs">per_page, page</td><td class="px-4 py-3">Paginación; máximo 100 registros por página.</td></tr></tbody></table></div>
            <div class="mt-6 rounded-lg border border-neza-line bg-neza-canvas p-4"><h3 class="text-sm font-bold text-neza-ink">Ejemplo de respuesta</h3><p class="mt-1 text-xs text-neza-muted">La respuesta incluye los trámites en <code class="font-mono">data</code> y la información de paginación en <code class="font-mono">links</code> y <code class="font-mono">meta</code>.</p><pre class="mt-3 overflow-x-auto rounded-md border border-neza-line bg-neza-ink p-3 text-xs leading-6 text-white"><code>{
  "data": [
    {
      "id": 1,
      "clave": "TRM-AB12CD34",
      "nombre": "Licencia única de construcción",
      "descripcion": "Trámite para solicitar una licencia de construcción.",
      "doc_a_obtener": "Licencia única de construcción",
      "vigencia_doc": "Un año",
      "sujeto_inspeccion": null,
      "criterios_resolucion": "Cumplir con los requisitos aplicables.",
      "unidad_administrativa_responsable": "Dirección de Desarrollo Urbano",
      "departamento": { "id": "12", "nombre": "DIRECCIÓN DE DESARROLLO URBANO" },
      "estado": { "id": 2, "clave": "PUBLICADO", "nombre": "Publicado" },
      "activo": true,
      "tiempo_estimado": "10 días hábiles",
      "costo": ["$350.00 PESOS"],
      "costo_pago": "350.00",
      "flujo_pago": "PAGO_DIRECTO",
      "pago_vigencia_dias": 30,
      "modalidades": ["EN_LINEA", "PRESENCIAL"],
      "requisitos": [{ "titulo": "Persona física", "requisitos": ["Identificación oficial vigente"] }],
      "proceso": ["Captura tu solicitud", "Adjunta los documentos"],
      "preguntas_frecuentes": [{ "pregunta": "¿Dónde se realiza?", "respuesta": "En línea o presencial." }],
      "descarga_expira_dias": 30,
      "created_at": "2026-11-10T15:00:00.000000Z",
      "updated_at": "2026-11-10T15:00:00.000000Z"
    }
  ],
  "links": { "first": "http://127.0.0.1:8000/api/v1/tramites?page=1", "last": "http://127.0.0.1:8000/api/v1/tramites?page=1", "prev": null, "next": null },
  "meta": { "current_page": 1, "from": 1, "last_page": 1, "per_page": 20, "to": 1, "total": 1 }
}</code></pre></div>
        </section>

        <section class="rounded-xl border border-neza-line bg-white p-5 shadow-sm sm:p-6" aria-labelledby="documentacion-api-retys">
            <div><p class="text-sm font-semibold text-neza-wine">RETyS</p><h2 id="documentacion-api-retys" class="mt-1 text-xl font-bold text-neza-ink">Consulta de información para RETyS</h2><p class="mt-2 text-sm text-neza-muted">Consulta un trámite por su clave o busca por nombre. Se requiere una API Key activa mediante Bearer Token.</p></div>
            <div class="mt-6 grid gap-5 lg:grid-cols-2"><div class="rounded-lg border border-neza-line bg-neza-canvas p-4"><h3 class="text-sm font-bold text-neza-ink">Endpoint</h3><code class="mt-3 block break-all rounded-md border border-neza-line bg-white px-3 py-2 font-mono text-xs text-neza-ink">GET {{ url('/api/v1/retys/tramites') }}</code><p class="mt-4 text-xs text-neza-muted">Envía exactamente uno de estos parámetros: <code class="font-mono">clave</code> o <code class="font-mono">nombre</code>.</p><h3 class="mt-5 text-sm font-bold text-neza-ink">Listado reducido</h3><code class="mt-3 block break-all rounded-md border border-neza-line bg-white px-3 py-2 font-mono text-xs text-neza-ink">GET {{ url('/api/v1/retys/tramites/lista') }}</code><p class="mt-2 text-xs text-neza-muted">Devuelve todos los trámites con solo <code class="font-mono">clave</code> y <code class="font-mono">nombre</code>.</p></div><div class="rounded-lg border border-neza-line bg-neza-canvas p-4"><h3 class="text-sm font-bold text-neza-ink">Ejemplos con cURL</h3><pre class="mt-3 overflow-x-auto rounded-md border border-neza-line bg-neza-ink p-3 text-xs leading-6 text-white"><code>curl "{{ url('/api/v1/retys/tramites') }}?clave=DDU/05/2026" \
  -H "Authorization: Bearer pt_live_tu_api_key" \
  -H "Accept: application/json"

curl "{{ url('/api/v1/retys/tramites') }}?nombre=Constancia%20de%20regularización" \
  -H "Authorization: Bearer pt_live_tu_api_key" \
  -H "Accept: application/json"</code></pre></div></div>
            <div class="mt-6 rounded-lg border border-neza-line bg-neza-canvas p-4"><h3 class="text-sm font-bold text-neza-ink">Respuesta</h3><p class="mt-1 text-xs text-neza-muted"><code class="font-mono">tipo</code> será <code class="font-mono">null</code> hasta que se agregue ese campo a la tabla de trámites.</p><pre class="mt-3 overflow-x-auto rounded-md border border-neza-line bg-neza-ink p-3 text-xs leading-6 text-white"><code>{
  "data": [
    {
      "clave": "DDU/05/2026",
      "nombre": "Constancia de regularización",
      "area": "DIRECCIÓN DE DESARROLLO URBANO",
      "tipo": null,
      "fundamento_juridico": "Fundamento aplicable al trámite.",
      "costo": ["$350.00 PESOS"],
      "requisitos": [{ "titulo": "Requisitos generales", "requisitos": ["Identificación oficial vigente"] }],
      "tiempo": "10 días hábiles"
    }
  ]
}</code></pre></div>
        </section>
    </div>

    @if (session('api_key_generada'))
        <span hidden data-api-key-generada data-api-key="{{ session('api_key_generada') }}"></span>
    @endif
@endsection
