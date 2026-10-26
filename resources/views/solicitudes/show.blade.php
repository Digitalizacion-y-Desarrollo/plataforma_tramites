@extends('layouts.app')

@section('content')
    @php
        $estilosEstado = [
            'BORRADOR' => 'bg-slate-100 text-slate-700',
            'ENVIADA' => 'bg-amber-50 text-amber-800',
            'CORRECCION_SOLICITADA' => 'bg-orange-50 text-orange-800',
            'VALIDADA_PARA_PAGO' => 'bg-sky-50 text-sky-800',
            'EN_ESPERA_DE_PAGO' => 'bg-amber-50 text-amber-800',
            'EN_REVISION_FINAL' => 'bg-violet-50 text-violet-800',
            'APROBADA' => 'bg-emerald-50 text-emerald-700',
            'RECHAZADA' => 'bg-rose-50 text-rose-700',
        ];
        $tieneRespuestas = $respuestasPorSeccion->isNotEmpty();
        $tieneDocumentos = $documentos->isNotEmpty();
        $datosPersonales = collect([
            'Nombre(s)' => $solicitud->nombres_solicitante,
            'Apellido paterno' => $solicitud->apellido_paterno_solicitante,
            'Apellido materno' => $solicitud->apellido_materno_solicitante,
            'CURP' => $solicitud->curp_consulta,
            'Correo electrónico' => $solicitud->email_contacto,
            'Teléfono' => $solicitud->telefono_contacto,
        ])->filter(fn ($valor) => filled($valor));
        $tieneDatosProporcionados = $datosPersonales->isNotEmpty() || $tieneRespuestas;
    @endphp

    <div class="mx-auto max-w-[1200px] space-y-6">
        <a href="{{ route('solicitudes.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-neza-wine hover:underline"><svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>Volver a solicitudes</a>

        <section class="overflow-hidden rounded-2xl border border-neza-line bg-white shadow-sm" aria-labelledby="expediente-titulo">
            <div class="border-b border-neza-line bg-gradient-to-r from-neza-wine-soft to-white px-5 py-5 sm:px-8 sm:py-7">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-neza-wine">Expediente de solicitud</p>
                        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-2"><p class="font-mono text-sm font-bold text-neza-wine">{{ $solicitud->folio }}</p>@if (filled($solicitud->estado?->nombre))<span class="rounded-full px-3 py-1 text-xs font-bold {{ $estilosEstado[$solicitud->estado?->clave] ?? 'bg-slate-100 text-slate-700' }}">{{ $solicitud->estado->nombre }}</span>@endif</div>
                        @if (filled($solicitud->tramite?->nombre))<h1 id="expediente-titulo" class="mt-3 text-2xl font-bold tracking-tight text-neza-ink sm:text-3xl">{{ $solicitud->tramite->nombre }}</h1>@endif
                        @if ($solicitud->fecha_envio)<p class="mt-2 text-sm text-neza-muted">Recibida el {{ $solicitud->fecha_envio->translatedFormat('d \d\e F \d\e Y, H:i') }}.</p>@endif
                    </div>
                    @if ($tieneRespuestas || $tieneDocumentos)<p class="w-fit rounded-lg border border-neza-line bg-white px-3 py-2 text-xs font-medium text-neza-muted">Revisa los datos y documentos antes de tomar una decisión.</p>@endif
                </div>
            </div>
        </section>

        @if ($solicitud->flujo_pago !== 'SIN_PAGO' && $solicitud->pago)
            <section class="rounded-2xl border border-neza-line bg-white p-5 shadow-sm" aria-labelledby="pago-titulo">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="pago-titulo" class="text-base font-bold text-neza-ink">Pago del trámite</h2>
                        <p class="mt-1 text-sm text-neza-muted">Ficha de pago enviada &middot; Importe ${{ number_format((float) $solicitud->pago->importe, 2) }} MXN &middot; Vence {{ $solicitud->pago->fecha_vencimiento->format('d/m/Y') }}</p>
                        @if (filled($solicitud->pago->fichaVigente?->indicaciones))<p class="mt-2 whitespace-pre-line text-sm text-neza-ink">{{ $solicitud->pago->fichaVigente->indicaciones }}</p>@endif
                        @if ($solicitud->pago->tieneComprobante())
                            <p class="mt-1 text-xs text-neza-muted">Comprobante recibido el {{ $solicitud->pago->fecha_comprobante?->format('d/m/Y H:i') }}.</p>
                        @endif
                    </div>
                    @if ($solicitud->pago->tieneComprobante())
                        <a href="{{ route('solicitudes.pagos.comprobante.descargar', $solicitud) }}" class="inline-flex items-center gap-2 rounded-lg border border-neza-wine px-4 py-2 text-sm font-semibold text-neza-wine hover:bg-neza-wine-soft">
                            <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5v-9m0 9-3.75-3.75M12 16.5l3.75-3.75M3.75 17.25v1.5A2.25 2.25 0 0 0 6 21h12a2.25 2.25 0 0 0 2.25-2.25v-1.5" /></svg>
                            Descargar comprobante de pago
                        </a>
                    @else
                        <span class="rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">Comprobante pendiente</span>
                    @endif
                </div>
            </section>
        @endif

        @php($modalidades = collect($solicitud->tramite?->modalidades ?? []))
        @php($puedeRechazar = in_array($solicitud->estado?->clave, ['ENVIADA', 'CORRECCION_SOLICITADA', 'VALIDADA_PARA_PAGO', 'EN_ESPERA_DE_PAGO', 'EN_REVISION_FINAL'], true))
        @php($puedeSolicitarCorreccion = $solicitud->estado?->clave === 'ENVIADA' && $archivosCorregibles->isNotEmpty())
        @php($puedeAprobar = (($solicitud->flujo_pago === 'SIN_PAGO' && $solicitud->estado?->clave === 'ENVIADA') || ($solicitud->flujo_pago !== 'SIN_PAGO' && $solicitud->estado?->clave === 'EN_REVISION_FINAL')) && ($modalidades->contains('EN_LINEA') || $modalidades->contains('PRESENCIAL')))
        @if ($solicitud->flujo_pago !== 'SIN_PAGO')
            <section class="flex flex-wrap justify-end gap-3">
                @if ($solicitud->flujo_pago === 'PAGO_POST_VALIDACION' && $solicitud->estado?->clave === 'ENVIADA')<form action="{{ route('solicitudes.validar', $solicitud) }}" method="POST" data-confirm-action data-confirm-title="¿Validar información?" data-confirm-text="Este cambio no podrá revertirse." data-confirm-button="Sí, validar">@csrf<button class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Validar información</button></form>@endif
                @if (($solicitud->flujo_pago === 'PAGO_DIRECTO' && $solicitud->estado?->clave === 'ENVIADA') || ($solicitud->flujo_pago === 'PAGO_POST_VALIDACION' && $solicitud->estado?->clave === 'VALIDADA_PARA_PAGO'))<button type="button" data-modal-open="{{ $origenFichaPago === 'SISTEMA_EXTERNO' ? 'enviar-ficha-pago' : 'previsualizar-referencia-pago' }}" class="rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">{{ $origenFichaPago === 'SISTEMA_EXTERNO' ? 'Enviar ficha de pago' : 'Generar ficha de pago' }}</button>@endif
                @if ($solicitud->estado?->clave === 'EN_ESPERA_DE_PAGO')<button type="button" data-modal-open="validar-pago" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Validar pago</button>@endif
                @if ($origenFichaPago === 'SISTEMA_EXTERNO' && $solicitud->estado?->clave === 'EN_ESPERA_DE_PAGO' && $solicitud->pago?->fichaVigente && ! $solicitud->pago->vencido())<button type="button" data-modal-open="reemplazar-ficha-pago" class="rounded-lg border border-neza-wine px-4 py-2.5 text-sm font-semibold text-neza-wine hover:bg-neza-wine-soft">Reemplazar ficha de pago</button>@endif
                @if ($puedeRechazar)<button type="button" data-modal-open="rechazar-pago" class="rounded-lg border border-rose-700 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-50">Rechazar solicitud</button>@endif
                @if ($puedeAprobar)<a href="{{ route('solicitudes.show', [$solicitud, 'aprobar' => 1]) }}" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Aprobar solicitud</a>@endif
            </section>
            @if ($origenFichaPago === 'SISTEMA_EXTERNO' && (($solicitud->flujo_pago === 'PAGO_DIRECTO' && $solicitud->estado?->clave === 'ENVIADA') || ($solicitud->flujo_pago === 'PAGO_POST_VALIDACION' && $solicitud->estado?->clave === 'VALIDADA_PARA_PAGO')))
                <dialog id="enviar-ficha-pago" @if ($errors->has('ficha') || $errors->has('indicaciones')) open @endif class="m-auto w-[min(96vw,620px)] rounded-xl border border-neza-line bg-white p-0 shadow-2xl backdrop:bg-neza-ink/50">
                    <div class="flex items-start justify-between gap-4 border-b border-neza-line px-6 py-4"><div><h2 class="text-lg font-bold text-neza-ink">Enviar ficha de pago</h2><p class="mt-1 text-sm text-neza-muted">Carga la ficha oficial en PDF e indica a la ciudadanía cómo realizar el pago.</p></div><button type="button" data-modal-close="enviar-ficha-pago" class="rounded-lg p-2 text-neza-muted hover:bg-neza-canvas" aria-label="Cerrar"><svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button></div>
                    <form action="{{ route('solicitudes.pagos.ficha.enviar', $solicitud) }}" method="POST" enctype="multipart/form-data" class="space-y-5 p-6" data-confirm-action data-confirm-title="¿Enviar ficha de pago?" data-confirm-text="La ficha se guardará en el expediente y se enviará al correo de la ciudadanía." data-confirm-button="Sí, enviar ficha">
                        @csrf
                        <div><label for="ficha-pago" class="block text-sm font-semibold text-neza-ink">Ficha de pago <span class="text-rose-700">*</span></label><input id="ficha-pago" name="ficha" type="file" accept="application/pdf,.pdf" required class="mt-2 block w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm text-neza-ink file:mr-3 file:rounded-md file:border-0 file:bg-neza-wine-soft file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-neza-wine @error('ficha') border-rose-600 @enderror"><p class="mt-1 text-xs text-neza-muted">Solo PDF. Tamaño máximo: 10 MB.</p>@error('ficha')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <div><label for="indicaciones-ficha" class="block text-sm font-semibold text-neza-ink">Indicaciones <span class="text-rose-700">*</span></label><textarea id="indicaciones-ficha" name="indicaciones" required maxlength="5000" class="mt-2 min-h-28 w-full rounded-lg border border-neza-line px-3 py-2 text-sm text-neza-ink focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('indicaciones') border-rose-600 @enderror">{{ old('indicaciones') }}</textarea>@error('indicaciones')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button type="button" data-modal-close="enviar-ficha-pago" class="rounded-lg border border-neza-line px-4 py-2.5 text-sm font-semibold text-neza-ink hover:bg-neza-canvas">Cancelar</button><button type="submit" class="rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Enviar ficha de pago</button></div>
                    </form>
                </dialog>
            @endif
            @if ($origenFichaPago === 'GENERADA_EN_PORTAL' && (($solicitud->flujo_pago === 'PAGO_DIRECTO' && $solicitud->estado?->clave === 'ENVIADA') || ($solicitud->flujo_pago === 'PAGO_POST_VALIDACION' && $solicitud->estado?->clave === 'VALIDADA_PARA_PAGO')))
                <dialog id="previsualizar-referencia-pago" data-referencia-pago-preview class="m-auto w-[min(96vw,960px)] rounded-xl border border-neza-line bg-white p-0 shadow-2xl backdrop:bg-neza-ink/50"><div class="flex items-start justify-between gap-4 border-b border-neza-line px-6 py-4"><div><h2 class="text-lg font-bold text-neza-ink">Vista previa de la ficha de pago</h2><p class="mt-1 text-sm text-neza-muted">Revisa el documento antes de enviarlo a la ciudadanía.</p></div><button type="button" data-modal-close="previsualizar-referencia-pago" class="rounded-lg p-2 text-neza-muted hover:bg-neza-canvas" aria-label="Cerrar"><svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button></div><div class="bg-neza-canvas p-4 sm:p-6"><iframe data-referencia-pago-preview-frame src="{{ route('solicitudes.pagos.previsualizar', $solicitud) }}" title="Vista previa de la ficha de pago" class="h-[55vh] min-h-96 w-full rounded-lg border border-neza-line bg-white"></iframe></div><form action="{{ route('solicitudes.pagos.generar', $solicitud) }}" method="POST" class="flex flex-col-reverse gap-3 border-t border-neza-line px-6 py-4 sm:flex-row sm:items-center sm:justify-end" data-confirm-action data-confirm-title="¿Enviar ficha de pago?" data-confirm-text="La ficha generada se guardará en el expediente y se enviará al correo de la ciudadanía." data-confirm-button="Sí, enviar ficha">@csrf<button type="button" data-modal-close="previsualizar-referencia-pago" class="rounded-lg border border-neza-line px-4 py-2.5 text-sm font-semibold text-neza-ink hover:bg-neza-canvas">Cancelar</button><button type="submit" data-referencia-pago-confirmar disabled class="rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50">Enviar ficha de pago</button></form></dialog>
            @endif
            <dialog id="reemplazar-ficha-pago" @if ($errors->has('motivo_reemplazo')) open @endif class="m-auto w-[min(96vw,620px)] rounded-xl border border-neza-line bg-white p-0 shadow-2xl backdrop:bg-neza-ink/50"><div class="flex items-start justify-between gap-4 border-b border-neza-line px-6 py-4"><div><h2 class="text-lg font-bold text-neza-ink">Reemplazar ficha de pago</h2><p class="mt-1 text-sm text-neza-muted">La ficha actual quedará conservada en el historial del expediente.</p></div><button type="button" data-modal-close="reemplazar-ficha-pago" class="rounded-lg p-2 text-neza-muted hover:bg-neza-canvas" aria-label="Cerrar"><svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button></div><form action="{{ route('solicitudes.pagos.ficha.reemplazar', $solicitud) }}" method="POST" enctype="multipart/form-data" class="space-y-5 p-6" data-confirm-action data-confirm-title="¿Reemplazar ficha de pago?" data-confirm-text="La nueva ficha se enviará automáticamente a la ciudadanía." data-confirm-button="Sí, reemplazar y enviar">@csrf<div><label for="ficha-reemplazo" class="block text-sm font-semibold text-neza-ink">Nueva ficha de pago <span class="text-rose-700">*</span></label><input id="ficha-reemplazo" name="ficha" type="file" accept="application/pdf,.pdf" required class="mt-2 block w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm text-neza-ink file:mr-3 file:rounded-md file:border-0 file:bg-neza-wine-soft file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-neza-wine @error('ficha') border-rose-600 @enderror">@error('ficha')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div><div><label for="indicaciones-reemplazo" class="block text-sm font-semibold text-neza-ink">Indicaciones <span class="text-rose-700">*</span></label><textarea id="indicaciones-reemplazo" name="indicaciones" required maxlength="5000" class="mt-2 min-h-24 w-full rounded-lg border border-neza-line px-3 py-2 text-sm @error('indicaciones') border-rose-600 @enderror">{{ old('indicaciones', $solicitud->pago?->fichaVigente?->indicaciones) }}</textarea>@error('indicaciones')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div><div><label for="motivo-reemplazo" class="block text-sm font-semibold text-neza-ink">Justificación del reemplazo <span class="text-rose-700">*</span></label><textarea id="motivo-reemplazo" name="motivo_reemplazo" required maxlength="5000" class="mt-2 min-h-24 w-full rounded-lg border border-neza-line px-3 py-2 text-sm @error('motivo_reemplazo') border-rose-600 @enderror">{{ old('motivo_reemplazo') }}</textarea>@error('motivo_reemplazo')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div><div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button type="button" data-modal-close="reemplazar-ficha-pago" class="rounded-lg border border-neza-line px-4 py-2.5 text-sm font-semibold text-neza-ink hover:bg-neza-canvas">Cancelar</button><button type="submit" class="rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Reemplazar y enviar</button></div></form></dialog>
            <dialog id="validar-pago" class="m-auto w-[min(96vw,620px)] rounded-xl border border-neza-line bg-white p-0 shadow-2xl backdrop:bg-neza-ink/50"><div class="border-b border-neza-line px-6 py-4"><h2 class="text-lg font-bold">Validar pago</h2></div><form action="{{ route('solicitudes.pagos.validar', $solicitud) }}" method="POST" class="p-6" data-confirm-action data-confirm-title="¿Validar pago?" data-confirm-text="El pago se marcará como confirmado y pasará a revisión final." data-confirm-button="Sí, validar pago">@csrf @if ($solicitud->pago?->tieneComprobante())<a href="{{ route('solicitudes.pagos.comprobante.descargar', $solicitud) }}" class="text-sm font-semibold text-neza-wine underline-offset-2 hover:underline">Descargar comprobante de pago</a>@else<p class="text-sm font-semibold text-amber-800">La ciudadanía aún no ha subido el comprobante de pago.</p>@endif<p class="mt-2 text-sm text-neza-muted">Confirma que el pago fue validado para enviarlo a revisión final.</p><button class="mt-4 rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white">Validar pago</button></form></dialog>
            <dialog id="rechazar-pago" class="m-auto w-[min(96vw,620px)] rounded-xl border border-neza-line bg-white p-0 shadow-2xl backdrop:bg-neza-ink/50"><div class="flex items-center justify-between border-b border-neza-line px-6 py-4"><h2 class="text-lg font-bold">Rechazar solicitud</h2><button type="button" data-modal-close="rechazar-pago" class="rounded-lg p-2 text-neza-muted hover:bg-neza-canvas" aria-label="Cerrar rechazo"><svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button></div><form action="{{ route('solicitudes.rechazar', $solicitud) }}" method="POST" class="p-6" data-confirm-action data-confirm-title="¿Rechazar solicitud?" data-confirm-text="El motivo se enviará a la ciudadanía." data-confirm-button="Sí, rechazar">@csrf<label for="motivo-pago" class="block text-sm font-semibold">Motivo <span class="text-rose-700">*</span></label><textarea id="motivo-pago" name="motivo" required maxlength="5000" class="mt-2 min-h-28 w-full rounded-lg border border-neza-line px-3 py-2 text-sm"></textarea><div class="mt-4 flex flex-wrap justify-end gap-3"><button type="button" data-modal-close="rechazar-pago" class="rounded-lg border border-neza-line px-4 py-2.5 text-sm font-semibold text-neza-ink hover:bg-neza-canvas">Cancelar</button><button type="submit" class="rounded-lg bg-rose-700 px-4 py-2.5 text-sm font-semibold text-white">Rechazar</button></div></form></dialog>
        @endif
        <dialog id="comentario" @if ($errors->has('comentario')) open @endif class="m-auto w-[min(96vw,620px)] rounded-xl border border-neza-line bg-white p-0 shadow-2xl backdrop:bg-neza-ink/50">
            <div class="flex items-center justify-between border-b border-neza-line px-6 py-4"><h2 class="text-lg font-bold text-neza-ink">Comentario para la ciudadanía</h2><button type="button" data-modal-close="comentario" class="rounded-lg p-2 text-neza-muted hover:bg-neza-canvas" aria-label="Cancelar y cerrar"><svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button></div>
            <form action="{{ route('solicitudes.comentarios.store', $solicitud) }}" method="POST" class="p-6" data-confirm-action data-confirm-title="¿Enviar comentario?" data-confirm-text="Se publicará en el expediente ciudadano y se enviará por correo." data-confirm-button="Sí, enviar">
                @csrf
                <label for="comentario" class="block text-sm font-semibold text-neza-ink">Mensaje <span class="text-rose-700">*</span></label>
                <textarea id="comentario" name="comentario" required maxlength="5000" class="mt-2 min-h-28 w-full rounded-lg border border-neza-line px-3 py-2 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('comentario') border-rose-600 @enderror">{{ old('comentario') }}</textarea>
                @error('comentario')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                <div class="mt-4 flex flex-wrap justify-end gap-3"><button type="button" data-modal-close="comentario" class="rounded-lg border border-neza-line px-4 py-2.5 text-sm font-semibold text-neza-ink hover:bg-neza-canvas">Cancelar</button><button type="submit" class="rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Enviar comentario</button></div>
            </form>
        </dialog>
        @if ($solicitud->pago && (filled($solicitud->pago->referencia) || $solicitud->pago->importe !== null || $solicitud->pago->fecha_vencimiento))<section class="rounded-xl border border-neza-line bg-white p-6 shadow-sm"><h2 class="text-lg font-bold text-neza-ink">Referencia de pago</h2><dl class="mt-4 grid gap-4 text-sm sm:grid-cols-3">@if (filled($solicitud->pago->referencia))<div><dt class="text-neza-muted">Referencia</dt><dd class="mt-1 font-mono font-semibold">{{ $solicitud->pago->referencia }}</dd></div>@endif @if ($solicitud->pago->importe !== null)<div><dt class="text-neza-muted">Importe</dt><dd class="mt-1 font-semibold">${{ number_format((float) $solicitud->pago->importe, 2) }} MXN</dd></div>@endif @if ($solicitud->pago->fecha_vencimiento)<div><dt class="text-neza-muted">Vigencia</dt><dd class="mt-1 font-semibold">{{ $solicitud->pago->fecha_vencimiento->format('d/m/Y') }}</dd></div>@endif</dl></section>@endif
        @if ($puedeRechazar && ($solicitud->flujo_pago === 'SIN_PAGO' || $puedeSolicitarCorreccion))
            @if ($solicitud->flujo_pago === 'SIN_PAGO')
            <section class="flex flex-wrap justify-end gap-3">
                @if ($puedeAprobar)
                    <button type="button" data-modal-open="aprobar" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Aprobar solicitud</button>
                @endif
                @if ($solicitud->flujo_pago === 'SIN_PAGO')<button type="button" data-modal-open="rechazar" class="rounded-lg border border-rose-700 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-50">Rechazar solicitud</button>@endif
            </section>
            @endif
            @if ($puedeSolicitarCorreccion)
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
                    <div class="mt-4 flex flex-wrap justify-end gap-3"><button type="button" data-modal-close="rechazar" class="rounded-lg border border-neza-line px-4 py-2.5 text-sm font-semibold text-neza-ink hover:bg-neza-canvas">Cancelar</button><button type="submit" class="rounded-lg bg-rose-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-800">Rechazar solicitud</button></div>
                </form>
            </dialog>
        @endif
        @if ($puedeAprobar)
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

        @if ($tieneDatosProporcionados)
            <section id="datos-proporcionados" class="scroll-mt-6 overflow-hidden rounded-xl border border-neza-line bg-white shadow-sm" aria-labelledby="datos-proporcionados-titulo">
                <div class="flex flex-col gap-2 border-b border-neza-line px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6"><div><h2 id="datos-proporcionados-titulo" class="text-lg font-bold text-neza-ink">Datos proporcionados</h2><p class="mt-1 text-sm text-neza-muted">Información capturada en el formulario enviado por la ciudadanía.</p></div>@if ($tieneRespuestas)<span class="w-fit rounded-full bg-neza-wine-soft px-3 py-1 text-xs font-bold text-neza-wine">{{ $respuestasPorSeccion->count() }} {{ $respuestasPorSeccion->count() === 1 ? 'sección' : 'secciones' }}</span>@endif</div>
                <div class="divide-y divide-neza-line">
                    @if ($datosPersonales->isNotEmpty())
                        <article class="p-5 sm:p-6">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <h3 class="text-base font-bold text-neza-ink">Datos de la persona solicitante</h3>
                                @if ($puedeEditarRespuestas)
                                    <button type="button" data-modal-open="editar-datos-personales" class="inline-flex w-fit items-center gap-2 rounded-lg border border-neza-wine px-3 py-2 text-xs font-semibold text-neza-wine hover:bg-neza-wine-soft">
                                        <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                        Editar
                                    </button>
                                @endif
                            </div>
                            <dl class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($datosPersonales as $etiqueta => $valor)
                                    <div class="rounded-lg border border-neza-line bg-neza-canvas px-4 py-3">
                                        <dt class="text-xs font-bold uppercase tracking-wide text-neza-muted">{{ $etiqueta }}</dt>
                                        <dd @class(['mt-1 break-words text-sm font-medium leading-6 text-neza-ink', 'font-mono' => $etiqueta === 'CURP'])>{{ $valor }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </article>
                    @endif
                    @foreach ($respuestasPorSeccion as $seccionId => $respuestas)
                        @php($seccionFormulario = $seccionesFormulario->get($seccionId))
                        <article class="p-5 sm:p-6">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <h3 class="text-base font-bold text-neza-ink">{{ $seccionFormulario?->titulo ?? $respuestas->first()->seccion_titulo }}</h3>
                                @if ($puedeEditarRespuestas && $seccionFormulario && $seccionFormulario->campos->where('tipo', '!=', 'FILE')->isNotEmpty())
                                    <button type="button" data-modal-open="editar-seccion-{{ $seccionId }}" class="inline-flex w-fit items-center gap-2 rounded-lg border border-neza-wine px-3 py-2 text-xs font-semibold text-neza-wine hover:bg-neza-wine-soft">
                                        <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                        Editar
                                    </button>
                                @endif
                            </div>
                            <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                                @foreach ($respuestas as $respuesta)
                                    <div class="rounded-lg border border-neza-line bg-neza-canvas px-4 py-3">
                                        <dt class="text-xs font-bold uppercase tracking-wide text-neza-muted">{{ $respuesta->campo_etiqueta }}</dt>
                                        <dd class="mt-1 whitespace-pre-wrap break-words text-sm font-medium leading-6 text-neza-ink">{{ $respuesta->valor }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($puedeEditarRespuestas && $tieneDatosProporcionados)
            @if ($datosPersonales->isNotEmpty())
                <dialog id="editar-datos-personales" @if (old('formulario') === 'datos-personales') open @endif class="m-auto w-[min(96vw,760px)] rounded-xl border border-neza-line bg-white p-0 shadow-2xl backdrop:bg-neza-ink/50">
                    <div class="flex items-start justify-between gap-4 border-b border-neza-line px-6 py-4">
                        <div>
                            <h2 class="text-lg font-bold text-neza-ink">Editar datos de la persona solicitante</h2>
                            <p class="mt-1 text-sm text-neza-muted">Corrige errores de captura u ortografía. Los cambios quedarán registrados en la auditoría del expediente.</p>
                        </div>
                        <button type="button" data-modal-close="editar-datos-personales" class="rounded-lg p-2 text-neza-muted hover:bg-neza-canvas" aria-label="Cerrar edición de datos personales"><svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
                    </div>
                    <form action="{{ route('solicitudes.datos-personales.actualizar', $solicitud) }}" method="POST" class="p-6" data-confirm-action data-confirm-title="¿Guardar cambios?" data-confirm-text="Se actualizarán los datos de la persona solicitante y quedará registro de la edición en la auditoría." data-confirm-button="Sí, guardar">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="formulario" value="datos-personales">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="editar-nombres-solicitante" class="block text-sm font-semibold text-neza-ink">Nombre(s) <span class="text-rose-700">*</span></label>
                                <input id="editar-nombres-solicitante" name="nombres_solicitante" value="{{ old('nombres_solicitante', $solicitud->nombres_solicitante) }}" maxlength="100" required class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('nombres_solicitante') border-rose-600 @enderror">
                                @error('nombres_solicitante')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="editar-paterno-solicitante" class="block text-sm font-semibold text-neza-ink">Apellido paterno <span class="text-rose-700">*</span></label>
                                <input id="editar-paterno-solicitante" name="apellido_paterno_solicitante" value="{{ old('apellido_paterno_solicitante', $solicitud->apellido_paterno_solicitante) }}" maxlength="100" required class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('apellido_paterno_solicitante') border-rose-600 @enderror">
                                @error('apellido_paterno_solicitante')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="editar-materno-solicitante" class="block text-sm font-semibold text-neza-ink">Apellido materno <span class="text-rose-700">*</span></label>
                                <input id="editar-materno-solicitante" name="apellido_materno_solicitante" value="{{ old('apellido_materno_solicitante', $solicitud->apellido_materno_solicitante) }}" maxlength="100" required class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('apellido_materno_solicitante') border-rose-600 @enderror">
                                @error('apellido_materno_solicitante')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="editar-curp-consulta" class="block text-sm font-semibold text-neza-ink">CURP <span class="text-rose-700">*</span></label>
                                <input id="editar-curp-consulta" name="curp_consulta" value="{{ old('curp_consulta', $solicitud->curp_consulta) }}" maxlength="18" required class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm uppercase focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('curp_consulta') border-rose-600 @enderror">
                                @error('curp_consulta')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="editar-email-contacto" class="block text-sm font-semibold text-neza-ink">Correo electrónico <span class="text-rose-700">*</span></label>
                                <input id="editar-email-contacto" name="email_contacto" type="email" value="{{ old('email_contacto', $solicitud->email_contacto) }}" maxlength="200" required class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('email_contacto') border-rose-600 @enderror">
                                @error('email_contacto')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="editar-telefono-contacto" class="block text-sm font-semibold text-neza-ink">Número telefónico <span class="text-rose-700">*</span></label>
                                <input id="editar-telefono-contacto" name="telefono_contacto" type="tel" value="{{ old('telefono_contacto', $solicitud->telefono_contacto) }}" maxlength="30" required class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('telefono_contacto') border-rose-600 @enderror">
                                @error('telefono_contacto')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">La CURP y el correo son la llave de consulta de la ciudadanía. Si los corriges, la persona deberá usar los nuevos datos para consultar su trámite.</p>
                        <div class="mt-5 flex flex-wrap justify-end gap-3">
                            <button type="button" data-modal-close="editar-datos-personales" class="rounded-lg border border-neza-line px-4 py-2.5 text-sm font-semibold text-neza-ink hover:bg-neza-canvas">Cancelar</button>
                            <button type="submit" class="rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Guardar cambios</button>
                        </div>
                    </form>
                </dialog>
            @endif

            @foreach ($respuestasPorSeccion as $seccionId => $respuestas)
                @php($seccionFormulario = $seccionesFormulario->get($seccionId))
                @php($camposEditables = $seccionFormulario?->campos->where('tipo', '!=', 'FILE'))
                @if ($seccionFormulario && $camposEditables->isNotEmpty())
                    <dialog id="editar-seccion-{{ $seccionId }}" @if (old('seccion') === $seccionId) open @endif class="m-auto w-[min(96vw,760px)] rounded-xl border border-neza-line bg-white p-0 shadow-2xl backdrop:bg-neza-ink/50">
                        <div class="flex items-start justify-between gap-4 border-b border-neza-line px-6 py-4">
                            <div>
                                <h2 class="text-lg font-bold text-neza-ink">Editar {{ $seccionFormulario->titulo }}</h2>
                                <p class="mt-1 text-sm text-neza-muted">Corrige errores de captura u ortografía. Los cambios quedarán registrados en la auditoría del expediente.</p>
                            </div>
                            <button type="button" data-modal-close="editar-seccion-{{ $seccionId }}" class="rounded-lg p-2 text-neza-muted hover:bg-neza-canvas" aria-label="Cerrar edición de {{ $seccionFormulario->titulo }}"><svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
                        </div>
                        <form action="{{ route('solicitudes.respuestas.actualizar', [$solicitud, $seccionId]) }}" method="POST" class="p-6" data-confirm-action data-confirm-title="¿Guardar cambios?" data-confirm-text="Se actualizarán los datos de esta sección y quedará registro de la edición en la auditoría." data-confirm-button="Sí, guardar">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="seccion" value="{{ $seccionId }}">
                            <div class="space-y-4">
                                @include('solicitudes.partials.campos-edicion', ['campos' => $camposEditables, 'valores' => $valoresRespuestas])
                            </div>
                            <div class="mt-5 flex flex-wrap justify-end gap-3">
                                <button type="button" data-modal-close="editar-seccion-{{ $seccionId }}" class="rounded-lg border border-neza-line px-4 py-2.5 text-sm font-semibold text-neza-ink hover:bg-neza-canvas">Cancelar</button>
                                <button type="submit" class="rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Guardar cambios</button>
                            </div>
                        </form>
                    </dialog>
                @endif
            @endforeach
        @endif

        @if ($tieneDocumentos)
            @if ($puedeSolicitarCorreccion)
                <section class="flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div><h2 class="text-sm font-bold text-amber-900">¿Algún documento requiere ajuste?</h2><p class="mt-1 text-sm text-amber-800">Solicita la corrección antes de continuar con la revisión del expediente.</p></div>
                    <button type="button" data-modal-open="solicitar-correccion" class="w-fit rounded-lg border border-amber-700 bg-white px-4 py-2.5 text-sm font-semibold text-amber-800 hover:bg-amber-100">Solicitar corrección</button>
                </section>
            @endif
            <section id="documentos-enviados" class="scroll-mt-6 overflow-hidden rounded-xl border border-neza-line bg-white shadow-sm" aria-labelledby="documentos-enviados-titulo"><div class="flex flex-col gap-2 border-b border-neza-line px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6"><div><h2 id="documentos-enviados-titulo" class="text-lg font-bold text-neza-ink">Documentos enviados</h2><p class="mt-1 text-sm text-neza-muted">Previsualiza y revisa los archivos del expediente antes de resolver.</p></div><span class="w-fit rounded-full bg-neza-wine-soft px-3 py-1 text-xs font-bold text-neza-wine">{{ $documentos->count() }} {{ $documentos->count() === 1 ? 'archivo' : 'archivos' }}</span></div><div class="divide-y divide-neza-line">@foreach ($documentos as $documento)<article class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6"><div class="flex min-w-0 items-start gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-lg bg-neza-wine-soft text-neza-wine" aria-hidden="true"><svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m-3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg></span><div class="min-w-0"><p class="font-semibold text-neza-ink">{{ $documento->requisito_nombre }}</p><p class="mt-1 truncate text-sm text-neza-muted">{{ $documento->nombre_original }} · {{ number_format($documento->tamano_bytes / 1024, 1) }} KB</p>@if ($documento->observaciones)<p class="mt-2 text-sm text-neza-muted">{{ $documento->observaciones }}</p>@endif</div></div><div class="flex flex-wrap items-center gap-2"><span class="order-last w-fit rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 sm:order-none">{{ $documento->estado_nombre }}</span><button type="button" data-document-preview data-document-name="{{ $documento->nombre_original }}" data-preview-url="{{ $documento->origen === 'DOCUMENTO' ? route('solicitudes.documentos.previsualizar', [$solicitud, $documento->id]) : route('solicitudes.archivos.previsualizar', [$solicitud, $documento->id]) }}" class="inline-flex items-center gap-2 rounded-lg border border-neza-line px-3 py-2 text-xs font-semibold text-neza-ink hover:bg-neza-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Previsualizar</button><a href="{{ $documento->origen === 'DOCUMENTO' ? route('solicitudes.documentos.descargar', [$solicitud, $documento->id]) : route('solicitudes.archivos.descargar', [$solicitud, $documento->id]) }}" class="inline-flex items-center gap-2 rounded-lg border border-neza-wine px-3 py-2 text-xs font-semibold text-neza-wine hover:bg-neza-wine-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Descargar</a></div></article>@endforeach</div></section>
        @endif

        <section id="comentarios" class="scroll-mt-6 rounded-xl border border-neza-line bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 border-b border-neza-line pb-5 sm:flex-row sm:items-start sm:justify-between">
                <div><h2 class="text-lg font-bold text-neza-ink">Historial de mensajes</h2><p class="mt-1 text-sm text-neza-muted">Comunicación entre el área responsable y la ciudadanía.</p></div>
                <button type="button" data-modal-open="comentario" class="w-fit rounded-lg border border-neza-wine px-4 py-2.5 text-sm font-semibold text-neza-wine hover:bg-neza-wine-soft">Enviar comentario</button>
            </div>
            <div class="mt-4 space-y-3">@forelse($comentarios as $comentario)<article @class(['rounded-lg p-4', 'bg-neza-wine-soft' => $comentario->actor_tipo === 'CIUDADANO', 'bg-neza-canvas' => $comentario->actor_tipo !== 'CIUDADANO'])><p class="text-xs font-bold text-neza-wine">{{ $comentario->actor_tipo === 'CIUDADANO' ? 'Ciudadanía' : ($comentario->actor_nombre_snapshot ?: 'Servidor público') }}</p><p class="mt-2 whitespace-pre-wrap text-sm text-neza-ink">{{ $comentario->texto }}</p><p class="mt-2 text-xs text-neza-muted">{{ \Illuminate\Support\Carbon::parse($comentario->created_at)->format('d/m/Y H:i') }}</p></article>@empty<p class="text-sm text-neza-muted">No se han enviado comentarios.</p>@endforelse</div>
        </section>

        <dialog id="documento-previsualizador" class="m-auto w-[min(96vw,1100px)] rounded-xl border border-neza-line p-0 shadow-2xl backdrop:bg-neza-ink/50"><div class="flex items-center justify-between border-b border-neza-line px-5 py-4"><h2 id="documento-previsualizador-titulo" class="truncate pr-4 text-base font-bold text-neza-ink">Previsualización</h2><button type="button" data-document-preview-close class="rounded-lg p-2 text-neza-muted hover:bg-neza-canvas hover:text-neza-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine" aria-label="Cerrar previsualización"><svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button></div><iframe id="documento-previsualizador-marco" title="Previsualización del documento" class="h-[72vh] w-full bg-neza-canvas" src="about:blank"></iframe></dialog>
    </div>

    <script>
        document.querySelectorAll('[data-domicilio-admin]').forEach((bloque) => {
            const url = bloque.dataset.indiceUrl;
            const codigoPostal = bloque.querySelector('[data-domicilio-campo="codigo_postal"]');
            const colonia = bloque.querySelector('[data-domicilio-campo="colonia"]');
            const colonias = bloque.querySelector('[data-domicilio-colonias]');
            const calles = bloque.querySelector('[data-domicilio-calles]');

            if (!url || !colonia || !colonias || !calles) return;

            let indice;
            const normalizar = (valor) => String(valor).trim().toLocaleUpperCase();
            const llenar = (lista, valores) => lista.replaceChildren(...valores.map((valor) => Object.assign(document.createElement('option'), { value: String(valor) })));
            const cargar = () => {
                if (!indice) {
                    indice = fetch(url, { headers: { Accept: 'application/json' } })
                        .then((respuesta) => respuesta.ok ? respuesta.json() : {})
                        .then((datos) => datos.porColonia ?? {});
                }

                return indice;
            };
            const actualizar = () => {
                cargar().then((datos) => {
                    const nombre = Object.keys(datos).find((clave) => normalizar(clave) === normalizar(colonia.value));

                    if (!nombre) return;

                    if (codigoPostal) {
                        codigoPostal.value = (datos[nombre].codigosPostales ?? [])[0] ?? codigoPostal.value;
                    }

                    llenar(calles, datos[nombre].calles ?? []);
                }).catch(() => {});
            };

            cargar().then((datos) => llenar(colonias, Object.keys(datos))).then(() => {
                if (colonia.value) actualizar();
            }).catch(() => {});

            colonia.addEventListener('change', actualizar);
        });
    </script>
@endsection

