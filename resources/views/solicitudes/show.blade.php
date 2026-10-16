@extends('layouts.app')

@section('content')
    @php
        $estilosEstado = [
            'BORRADOR' => 'bg-slate-100 text-slate-700',
            'ENVIADA' => 'bg-amber-50 text-amber-800',
            'CORRECCION_SOLICITADA' => 'bg-orange-50 text-orange-800',
            'APROBADA' => 'bg-emerald-50 text-emerald-700',
            'RECHAZADA' => 'bg-rose-50 text-rose-700',
        ];
    @endphp

    <div class="mx-auto max-w-[1200px] space-y-6">
        <a href="{{ route('solicitudes.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-neza-wine hover:underline"><svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>Volver a solicitudes</a>

        <section class="rounded-xl border border-neza-line bg-white p-6 shadow-sm sm:p-8"><div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between"><div><p class="font-mono text-sm font-semibold text-neza-wine">{{ $solicitud->folio }}</p><h1 class="mt-2 text-3xl font-bold tracking-tight text-neza-ink">{{ $solicitud->tramite?->nombre ?? 'TrÃ¡mite no disponible' }}</h1><p class="mt-2 text-sm text-neza-muted">Solicitud enviada {{ $solicitud->fecha_envio?->translatedFormat('d \d\e F \d\e Y, H:i') ?? 'sin fecha registrada' }}.</p></div><span class="w-fit rounded-full px-3 py-1.5 text-sm font-semibold {{ $estilosEstado[$solicitud->estado?->clave] ?? 'bg-slate-100 text-slate-700' }}">{{ $solicitud->estado?->nombre ?? 'Sin estado' }}</span></div><dl class="mt-7 grid gap-5 border-t border-neza-line pt-6 sm:grid-cols-3"><div><dt class="text-xs font-medium text-neza-muted">Correo de contacto</dt><dd class="mt-1 text-sm font-semibold text-neza-ink">{{ $solicitud->email_contacto }}</dd></div><div><dt class="text-xs font-medium text-neza-muted">CURP de consulta</dt><dd class="mt-1 font-mono text-sm font-semibold text-neza-ink">{{ $solicitud->curp_consulta }}</dd></div><div><dt class="text-xs font-medium text-neza-muted">Fecha de terminaciÃ³n</dt><dd class="mt-1 text-sm font-semibold text-neza-ink">{{ $solicitud->fecha_terminacion?->translatedFormat('d M Y, H:i') ?? 'Pendiente' }}</dd></div></dl></section>

        @php($modalidades = collect($solicitud->tramite?->modalidades ?? []))
        @if ($solicitud->estado?->clave === 'ENVIADA')
            <section class="flex flex-wrap justify-end gap-3">
                @if ($modalidades->contains('EN_LINEA'))
                    <button type="button" data-modal-open="aprobar" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Aprobar solicitud</button>
                @endif
                @if ($archivosCorregibles->isNotEmpty())
                    <button type="button" data-modal-open="solicitar-correccion" class="rounded-lg border border-amber-700 px-4 py-2.5 text-sm font-semibold text-amber-800 hover:bg-amber-50">Solicitar corrección</button>
                @endif
                <button type="button" data-modal-open="rechazar" class="rounded-lg border border-rose-700 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-50">Rechazar solicitud</button>
            </section>
            @if ($archivosCorregibles->isNotEmpty())
                <dialog id="solicitar-correccion" @if ($errors->has('documentos') || $errors->has('documentos.*.motivo')) open @endif class="m-auto w-[min(96vw,760px)] rounded-xl border border-neza-line bg-white p-0 shadow-2xl backdrop:bg-neza-ink/50">
                    <div class="flex items-center justify-between border-b border-neza-line px-6 py-4"><div><h2 class="text-lg font-bold text-neza-ink">Solicitar corrección de documentos</h2><p class="mt-1 text-sm text-neza-muted">Selecciona uno o más archivos e indica el motivo para cada uno.</p></div><button type="button" data-modal-close="solicitar-correccion" class="rounded-lg p-2 text-neza-muted hover:bg-neza-canvas" aria-label="Cerrar"><svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button></div>
                    <form action="{{ route('solicitudes.correcciones.solicitar', $solicitud) }}" method="POST" class="space-y-4 p-6" data-confirm-action data-confirm-title="¿Solicitar corrección?" data-confirm-text="La ciudadanía recibirá un correo con acceso para reemplazar los documentos seleccionados." data-confirm-button="Sí, solicitar">
                        @csrf
                        @foreach ($archivosCorregibles as $archivo)
                            <article class="rounded-lg border border-neza-line p-4" data-correccion-documento>
                                <label class="flex cursor-pointer items-start gap-3"><input type="checkbox" name="documentos[{{ $archivo->campo_id }}][seleccionado]" value="1" data-correccion-selector @checked(old('documentos.'.$archivo->campo_id.'.seleccionado')) class="mt-1 size-4 rounded border-neza-line text-neza-wine focus:ring-neza-wine"><span><span class="block text-sm font-semibold text-neza-ink">{{ $archivo->requisito_nombre }}</span><span class="mt-1 block text-xs text-neza-muted">{{ $archivo->nombre_original }}</span></span></label>
                                <div class="mt-4 hidden" data-correccion-motivo><label for="motivo-{{ $archivo->campo_id }}" class="block text-sm font-semibold text-neza-ink">Motivo de corrección <span class="text-rose-700">*</span></label><textarea id="motivo-{{ $archivo->campo_id }}" name="documentos[{{ $archivo->campo_id }}][motivo]" disabled maxlength="5000" class="mt-2 min-h-24 w-full rounded-lg border border-neza-line px-3 py-2 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('documentos.'.$archivo->campo_id.'.motivo') border-rose-600 @enderror">{{ old('documentos.'.$archivo->campo_id.'.motivo') }}</textarea>@error('documentos.'.$archivo->campo_id.'.motivo')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                            </article>
                        @endforeach
                        @error('documentos')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
                        <button type="submit" class="rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Enviar solicitud de corrección</button>
                    </form>
                </dialog>
            @endif
            <dialog id="rechazar" @if ($errors->has('motivo')) open @endif class="m-auto w-[min(96vw,680px)] rounded-xl border border-neza-line bg-white p-0 shadow-2xl backdrop:bg-neza-ink/50">
                <div class="flex items-center justify-between border-b border-neza-line px-6 py-4"><h2 class="text-lg font-bold text-neza-ink">Rechazar solicitud</h2><button type="button" data-modal-close="rechazar" class="rounded-lg p-2 text-neza-muted hover:bg-neza-canvas" aria-label="Cerrar"><svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button></div>
                <form action="{{ route('solicitudes.rechazar', $solicitud) }}" method="POST" class="p-6" data-confirm-action data-confirm-title="¿Rechazar solicitud?" data-confirm-text="El motivo se enviará a la ciudadanía y quedará registrado en el expediente." data-confirm-button="Sí, rechazar">
                    @csrf
                    <label for="motivo" class="block text-sm font-semibold text-neza-ink">Motivo del rechazo <span class="text-rose-700">*</span></label>
                    <textarea id="motivo" name="motivo" required maxlength="5000" placeholder="Describe claramente el motivo del rechazo y, si corresponde, qué debe corregirse." class="mt-2 min-h-32 w-full rounded-lg border border-neza-line px-3 py-2 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('motivo') border-rose-600 @enderror">{{ old('motivo') }}</textarea>
                    @error('motivo')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                    <button type="submit" class="mt-4 rounded-lg bg-rose-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-800">Rechazar solicitud</button>
                </form>
            </dialog>
        @endif
        @if ($solicitud->estado?->clave === 'ENVIADA' && $modalidades->contains('EN_LINEA'))
            <dialog id="aprobar" @if (request('aprobar')) open @endif class="m-auto w-[min(96vw,680px)] rounded-xl border border-neza-line bg-white p-0 shadow-2xl backdrop:bg-neza-ink/50">
                <div class="flex items-center justify-between border-b border-neza-line px-6 py-4"><h2 class="text-lg font-bold text-neza-ink">Aprobar solicitud</h2><a href="{{ route('solicitudes.show', $solicitud) }}" class="rounded-lg p-2 text-neza-muted hover:bg-neza-canvas" aria-label="Cerrar aprobación"><svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></a></div>
                <div class="p-6">
                @if ($modalidades->contains('PRESENCIAL'))
                    <p class="mt-1 text-sm text-neza-muted">Indica las instrucciones obligatorias para que la ciudadanía concluya la atención presencial.</p>
                    <form action="{{ route('solicitudes.aprobar', $solicitud) }}" method="POST" class="mt-5" data-confirm-action data-confirm-title="¿Aprobar solicitud?" data-confirm-text="Las instrucciones se conservarán en el expediente." data-confirm-button="Sí, aprobar">
                        @csrf
                        <label for="instrucciones" class="block text-sm font-semibold text-neza-ink">Instrucciones para la ciudadanía <span class="text-rose-700">*</span></label>
                        <textarea id="instrucciones" name="instrucciones" required maxlength="5000" placeholder="Ej. Acude a la Dirección de Desarrollo Urbano, Av. Juárez 100, de lunes a viernes de 9:00 a 15:00, con identificación oficial y tu folio para concluir tu trámite." class="mt-2 min-h-32 w-full rounded-lg border border-neza-line px-3 py-2 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('instrucciones') border-rose-600 @enderror">{{ old('instrucciones') }}</textarea>
                        @error('instrucciones')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                        <button type="submit" class="mt-4 rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Aprobar solicitud</button>
                    </form>
                @else
                    <p class="mt-1 text-sm text-neza-muted">Carga el documento final que se entregará a la ciudadanía. Si lo necesitas, agrega indicaciones adicionales.</p>
                    <form action="{{ route('solicitudes.aprobar', $solicitud) }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4" data-confirm-action data-confirm-title="¿Aprobar solicitud?" data-confirm-text="El documento se enviará por correo y quedará disponible en la consulta segura." data-confirm-button="Sí, aprobar">
                        @csrf
                        <div><label for="documento_final" class="block text-sm font-semibold text-neza-ink">Documento final <span class="text-rose-700">*</span></label>
                        <input id="documento_final" name="documento_final" type="file" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="mt-2 block w-full rounded-lg border border-neza-line px-3 py-2 text-sm @error('documento_final') border-rose-600 @enderror">
                        <p class="mt-1 text-xs text-neza-muted">PDF, JPG, JPEG, PNG, DOC o DOCX. Máximo 10 MB.</p>
                        @error('documento_final')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <div><label for="instrucciones-linea" class="block text-sm font-semibold text-neza-ink">Indicaciones adicionales <span class="font-normal text-neza-muted">(opcional)</span></label>
                        <p class="mt-1 text-xs text-neza-muted">Se enviarán junto al documento. Úsalo para explicar vigencia, uso del documento o próximos pasos.</p>
                        <textarea id="instrucciones-linea" name="instrucciones" maxlength="5000" placeholder="Ej. Tu constancia es válida por un año. Descarga el PDF adjunto, imprímelo y preséntalo en ventanilla solo si te lo solicitan." class="mt-2 min-h-32 w-full rounded-lg border border-neza-line px-3 py-2 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('instrucciones') border-rose-600 @enderror">{{ old('instrucciones') }}</textarea>
                        @error('instrucciones')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <div><label for="expira-linea" class="block text-sm font-semibold text-neza-ink">Tiempo de expiración de descarga <span class="font-normal text-neza-muted">(días, opcional)</span></label>
                        <p class="mt-1 text-xs text-neza-muted">Después de ese tiempo la ciudadanía ya no podrá descargar el documento. Vacío = sin vencimiento. Ej. 30 días.</p>
                        <input id="expira-linea" name="descarga_expira_dias" type="number" min="1" max="3650" step="1" value="{{ old('descarga_expira_dias', $solicitud->tramite?->descarga_expira_dias) }}" placeholder="Ej. 30" class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('descarga_expira_dias') border-rose-600 @enderror">
                        @error('descarga_expira_dias')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <button type="submit" class="rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Aprobar solicitud</button>
                    </form>
                @endif
                </div>
            </dialog>
        @endif

        <section class="rounded-xl border border-neza-line bg-white shadow-sm"><div class="border-b border-neza-line px-6 py-5"><h2 class="text-lg font-bold text-neza-ink">Datos proporcionados</h2><p class="mt-1 text-sm text-neza-muted">InformaciÃ³n capturada en el formulario enviado por la ciudadanÃ­a.</p></div><div class="divide-y divide-neza-line">@forelse ($respuestasPorSeccion as $seccion => $respuestas)<article class="p-6"><h3 class="font-semibold text-neza-ink">{{ $seccion }}</h3><dl class="mt-5 grid gap-x-8 gap-y-5 sm:grid-cols-2">@foreach ($respuestas as $respuesta)<div><dt class="text-xs font-medium text-neza-muted">{{ $respuesta->campo_etiqueta }}</dt><dd class="mt-1 whitespace-pre-wrap text-sm text-neza-ink">{{ $respuesta->valor }}</dd></div>@endforeach</dl></article>@empty<div class="p-8 text-center"><p class="text-sm text-neza-muted">Esta solicitud no tiene respuestas registradas.</p></div>@endforelse</div></section>

        <section class="rounded-xl border border-neza-line bg-white shadow-sm"><div class="border-b border-neza-line px-6 py-5"><h2 class="text-lg font-bold text-neza-ink">Documentos enviados</h2><p class="mt-1 text-sm text-neza-muted">Consulta cada archivo antes de descargarlo.</p></div><div class="divide-y divide-neza-line">@forelse ($documentos as $documento)<article class="flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between"><div class="flex min-w-0 items-start gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-lg bg-neza-wine-soft text-neza-wine" aria-hidden="true"><svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg></span><div class="min-w-0"><p class="font-semibold text-neza-ink">{{ $documento->requisito_nombre }}</p><p class="mt-1 truncate text-sm text-neza-muted">{{ $documento->nombre_original }} Â· {{ number_format($documento->tamano_bytes / 1024, 1) }} KB</p>@if ($documento->observaciones)<p class="mt-2 text-sm text-neza-muted">{{ $documento->observaciones }}</p>@endif</div></div><div class="flex flex-wrap items-center gap-3"><button type="button" data-document-preview data-document-name="{{ $documento->nombre_original }}" data-preview-url="{{ $documento->origen === 'DOCUMENTO' ? route('solicitudes.documentos.previsualizar', [$solicitud, $documento->id]) : route('solicitudes.archivos.previsualizar', [$solicitud, $documento->id]) }}" class="inline-flex items-center gap-2 rounded-lg border border-neza-line px-3 py-2 text-xs font-semibold text-neza-ink hover:bg-neza-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine"><svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 12a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>Previsualizar</button><a href="{{ $documento->origen === 'DOCUMENTO' ? route('solicitudes.documentos.descargar', [$solicitud, $documento->id]) : route('solicitudes.archivos.descargar', [$solicitud, $documento->id]) }}" class="inline-flex items-center gap-2 rounded-lg border border-neza-wine px-3 py-2 text-xs font-semibold text-neza-wine hover:bg-neza-wine-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine"><svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5v-9m0 9-3.75-3.75M12 16.5l3.75-3.75M3.75 17.25v1.5A2.25 2.25 0 0 0 6 21h12a2.25 2.25 0 0 0 2.25-2.25v-1.5" /></svg>Descargar</a><span class="w-fit rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $documento->estado_nombre }}</span></div></article>@empty<div class="p-8 text-center"><p class="text-sm text-neza-muted">No se enviaron documentos para esta solicitud.</p></div>@endforelse</div></section>

        <dialog id="documento-previsualizador" class="m-auto w-[min(96vw,1100px)] rounded-xl border border-neza-line p-0 shadow-2xl backdrop:bg-neza-ink/50"><div class="flex items-center justify-between border-b border-neza-line px-5 py-4"><h2 id="documento-previsualizador-titulo" class="truncate pr-4 text-base font-bold text-neza-ink">Previsualización</h2><button type="button" data-document-preview-close class="rounded-lg p-2 text-neza-muted hover:bg-neza-canvas hover:text-neza-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine" aria-label="Cerrar previsualización"><svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button></div><iframe id="documento-previsualizador-marco" title="Previsualización del documento" class="h-[72vh] w-full bg-neza-canvas" src="about:blank"></iframe></dialog>
    </div>
@endsection

