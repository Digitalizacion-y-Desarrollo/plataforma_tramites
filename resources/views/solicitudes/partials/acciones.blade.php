@php
    $modalidades = collect($solicitud->tramite?->modalidades ?? []);
    $puedeAprobar = $solicitud->estado?->clave === 'ENVIADA' && $modalidades->contains('EN_LINEA');
    $esMixto = $modalidades->contains('PRESENCIAL');
    $contenedor = $movil ? 'flex flex-wrap gap-2 border-t border-neza-line pt-3' : 'flex justify-end gap-2';
    $boton = $movil ? 'flex-1 text-center' : '';
    $modalId = 'aprobar-'.$solicitud->id.($movil ? '-movil' : '-escritorio');
    $modalRechazoId = 'rechazar-'.$solicitud->id.($movil ? '-movil' : '-escritorio');
@endphp
<div class="{{ $contenedor }}">
    <a href="{{ route('solicitudes.show', $solicitud) }}" class="{{ $boton }} rounded-lg border border-neza-wine px-3 py-2 text-xs font-semibold text-neza-wine hover:bg-neza-wine-soft">Ver solicitud</a>
    @if ($puedeAprobar)
        <button type="button" data-modal-open="{{ $modalId }}" class="{{ $boton }} rounded-lg bg-emerald-700 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-800">Aprobar</button>
        <dialog id="{{ $modalId }}" class="m-auto w-[min(96vw,680px)] rounded-xl border border-neza-line bg-white p-0 shadow-2xl backdrop:bg-neza-ink/50">
            <div class="flex items-center justify-between border-b border-neza-line px-6 py-4"><h2 class="text-lg font-bold text-neza-ink">Aprobar solicitud</h2><button type="button" data-modal-close="{{ $modalId }}" class="rounded-lg p-2 text-neza-muted hover:bg-neza-canvas" aria-label="Cerrar"><svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button></div>
            <div class="p-6">
                @if ($esMixto)
                    <p class="text-sm text-neza-muted">Indica las instrucciones obligatorias para que la ciudadanía concluya la atención presencial.</p>
                    <form action="{{ route('solicitudes.aprobar', $solicitud) }}" method="POST" class="mt-5" data-confirm-action data-confirm-title="¿Aprobar solicitud?" data-confirm-text="Las instrucciones se conservarán en el expediente." data-confirm-button="Sí, aprobar">
                        @csrf
                        <label for="instrucciones-{{ $modalId }}" class="block text-sm font-semibold text-neza-ink">Instrucciones para la ciudadanía <span class="text-rose-700">*</span></label>
                        <textarea id="instrucciones-{{ $modalId }}" name="instrucciones" required maxlength="5000" placeholder="Ej. Acude a la Dirección de Desarrollo Urbano, Av. Juárez 100, de lunes a viernes de 9:00 a 15:00, con identificación oficial y tu folio para concluir tu trámite." class="mt-2 min-h-32 w-full rounded-lg border border-neza-line px-3 py-2 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"></textarea>
                        <button type="submit" class="mt-4 rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Aprobar solicitud</button>
                    </form>
                @else
                    <p class="text-sm text-neza-muted">Carga el documento final que se entregará a la ciudadanía. Si lo necesitas, agrega indicaciones adicionales.</p>
                    <form action="{{ route('solicitudes.aprobar', $solicitud) }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4" data-confirm-action data-confirm-title="¿Aprobar solicitud?" data-confirm-text="El documento se enviará por correo y quedará disponible en la consulta segura." data-confirm-button="Sí, aprobar">
                        @csrf
                        <div><label for="documento-final-{{ $modalId }}" class="block text-sm font-semibold text-neza-ink">Documento final <span class="text-rose-700">*</span></label>
                        <input id="documento-final-{{ $modalId }}" name="documento_final" type="file" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="mt-2 block w-full rounded-lg border border-neza-line px-3 py-2 text-sm">
                        <p class="mt-1 text-xs text-neza-muted">PDF, JPG, JPEG, PNG, DOC o DOCX. Máximo 10 MB.</p></div>
                        <div><label for="instrucciones-{{ $modalId }}" class="block text-sm font-semibold text-neza-ink">Indicaciones adicionales <span class="font-normal text-neza-muted">(opcional)</span></label>
                        <p class="mt-1 text-xs text-neza-muted">Se enviarán junto al documento. Úsalo para explicar vigencia, uso del documento o próximos pasos.</p>
                        <textarea id="instrucciones-{{ $modalId }}" name="instrucciones" maxlength="5000" placeholder="Ej. Tu constancia es válida por un año. Descarga el PDF adjunto, imprímelo y preséntalo en ventanilla solo si te lo solicitan." class="mt-2 min-h-32 w-full rounded-lg border border-neza-line px-3 py-2 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"></textarea></div>
                        <div><label for="expira-{{ $modalId }}" class="block text-sm font-semibold text-neza-ink">Tiempo de expiración de descarga <span class="font-normal text-neza-muted">(días, opcional)</span></label>
                        <p class="mt-1 text-xs text-neza-muted">Después de ese tiempo la ciudadanía ya no podrá descargar el documento. Vacío = sin vencimiento. Ej. 30 días.</p>
                        <input id="expira-{{ $modalId }}" name="descarga_expira_dias" type="number" min="1" max="3650" step="1" value="{{ $solicitud->tramite?->descarga_expira_dias }}" placeholder="Ej. 30" class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft"></div>
                        <button type="submit" class="rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Aprobar solicitud</button>
                    </form>
                @endif
            </div>
        </dialog>
    @endif
    @if ($solicitud->estado?->clave === 'ENVIADA')
        <button type="button" data-modal-open="{{ $modalRechazoId }}" class="{{ $boton }} rounded-lg border border-rose-700 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">Rechazar</button>
        <dialog id="{{ $modalRechazoId }}" class="m-auto w-[min(96vw,680px)] rounded-xl border border-neza-line bg-white p-0 shadow-2xl backdrop:bg-neza-ink/50">
            <div class="flex items-center justify-between border-b border-neza-line px-6 py-4"><h2 class="text-lg font-bold text-neza-ink">Rechazar solicitud</h2><button type="button" data-modal-close="{{ $modalRechazoId }}" class="rounded-lg p-2 text-neza-muted hover:bg-neza-canvas" aria-label="Cerrar"><svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button></div>
            <form action="{{ route('solicitudes.rechazar', $solicitud) }}" method="POST" class="p-6" data-confirm-action data-confirm-title="¿Rechazar solicitud?" data-confirm-text="El motivo se enviará a la ciudadanía y quedará registrado en el expediente." data-confirm-button="Sí, rechazar">
                @csrf
                <label for="motivo-{{ $modalRechazoId }}" class="block text-sm font-semibold text-neza-ink">Motivo del rechazo <span class="text-rose-700">*</span></label>
                <textarea id="motivo-{{ $modalRechazoId }}" name="motivo" required maxlength="5000" placeholder="Describe claramente el motivo del rechazo y, si corresponde, qué debe corregirse." class="mt-2 min-h-32 w-full rounded-lg border border-neza-line px-3 py-2 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('motivo') border-rose-600 @enderror">{{ old('motivo') }}</textarea>
                @error('motivo')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                <button type="submit" class="mt-4 rounded-lg bg-rose-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-800">Rechazar solicitud</button>
            </form>
        </dialog>
    @endif
</div>
