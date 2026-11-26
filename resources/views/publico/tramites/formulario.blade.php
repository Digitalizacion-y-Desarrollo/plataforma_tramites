@extends('layouts.publico')

@section('title', 'Iniciar '.$tramite->nombre)

@if ($formularioVersion->secciones->flatMap->campos->contains('tipo', 'MAPA'))
    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin="" defer></script>
    @endpush
@endif

@php($secciones = $formularioVersion->secciones->sortBy('orden'))
@php($totalSecciones = $secciones->count())

@section('content')
    <section class="bg-neza-canvas px-4 py-8 sm:px-6 lg:px-[72px]">
        <div class="mx-auto max-w-3xl">
            <a href="{{ route('portal.tramites.detalle', $tramite) }}" class="inline-flex min-h-11 items-center gap-2 text-base font-bold text-neza-wine underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-neza-wine">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                Volver al trámite
            </a>
            <div class="mt-5 rounded-2xl border border-neza-line bg-white p-6 shadow-sm sm:p-8">
                <p class="text-xs font-bold uppercase tracking-wide text-neza-wine">{{ $tramite->nombre }}</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Completa tu solicitud</h1>
                <p class="mt-3 max-w-2xl text-base leading-relaxed text-neza-muted">Te guiaremos paso a paso. Guarda a la mano tus documentos; puedes volver a la sección anterior si necesitas corregir algo.</p>
                <p class="mt-3 text-sm font-semibold text-neza-muted"><span class="text-rose-700" aria-hidden="true">*</span> Los campos con asterisco son obligatorios.</p>

                @if (session('success'))<div class="mt-5 rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>@endif
                @if ($errors->any())<div id="resumen-errores" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"><p class="font-bold">No se pudo guardar la solicitud.</p><ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><p class="mt-2">Si seleccionaste archivos, vuelve a adjuntarlos antes de enviar.</p></div>@endif

                @if ($totalSecciones > 0)
                    <nav aria-label="Progreso del formulario" class="mt-6 rounded-xl border border-neza-line bg-neza-canvas p-4 sm:p-5">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <p class="text-sm font-bold text-neza-ink" data-progreso-texto>Paso 1 de {{ $totalSecciones + 1 }}</p>
                            <p class="text-sm text-neza-muted">Puedes regresar cuando lo necesites.</p>
                        </div>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-neza-line" role="progressbar" aria-label="Avance del formulario" aria-valuemin="1" aria-valuemax="{{ $totalSecciones + 1 }}" aria-valuenow="1" data-progreso-barra><span class="block h-full w-full rounded-full bg-neza-wine"></span></div>
                        <ol class="mt-4 {{ $totalSecciones === 1 ? 'hidden' : 'grid gap-2 sm:grid-cols-2' }}">
                            <li data-progreso-paso="0" class="flex items-center gap-3 rounded-lg bg-white px-3 py-2 text-sm" aria-current="step">
                                <span class="grid size-7 shrink-0 place-items-center rounded-full bg-neza-wine text-xs font-bold text-white" aria-hidden="true">1</span>
                                <span class="font-semibold text-neza-ink">Datos para seguimiento</span>
                            </li>
                            @foreach ($secciones as $seccion)
                                <li data-progreso-paso="{{ $loop->iteration }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-neza-muted">
                                    <span class="grid size-7 shrink-0 place-items-center rounded-full border border-neza-line bg-white text-xs font-bold" aria-hidden="true">{{ $loop->iteration + 1 }}</span>
                                    <span class="font-semibold text-neza-ink">{{ $seccion->titulo }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                @endif

                <form action="{{ route('portal.tramites.solicitudes.store', $tramite) }}" method="POST" enctype="multipart/form-data" class="mt-8 space-y-8" aria-label="Formulario de {{ $tramite->nombre }}" data-formulario-preview data-submit-lock data-expediente-token-url="{{ route('portal.tramites.expedientes.token.solicitar') }}" data-expediente-token-verificar-url="{{ route('portal.tramites.expedientes.token.verificar') }}" data-expediente-sincronizar-url="{{ route('portal.tramites.expedientes.sincronizar') }}" data-csrf-token="{{ csrf_token() }}">
                    @csrf
                    <input type="hidden" name="formulario_version_id" value="{{ $formularioVersion->id }}">
                    <fieldset id="seccion-0" data-formulario-seccion class="space-y-5 border-b border-neza-line pb-6">
                        <legend class="flex flex-wrap items-center gap-3 text-xl font-bold">Datos para seguimiento <span class="rounded-full bg-neza-wine-soft px-3 py-1 text-xs font-bold text-neza-wine">Sección 1 de {{ $totalSecciones + 1 }}</span></legend>
                        <p class="-mt-2 text-base leading-relaxed text-neza-muted">Estos datos nos permiten identificar tu solicitud y mantenerte informado.</p>
                        <div data-expediente-paso="1">
                            <label for="curp_consulta" class="block text-sm font-semibold">CURP <span class="text-rose-700">*</span></label>
                            <input id="curp_consulta" name="curp_consulta" value="{{ old('curp_consulta') }}" maxlength="18" required autocomplete="off" placeholder="Escribe tus 18 caracteres" data-expediente-curp data-expediente-url="{{ route('portal.tramites.expedientes.verificar') }}" @error('curp_consulta') aria-invalid="true" aria-describedby="curp_consulta-error" @enderror class="mt-2 min-h-12 w-full rounded-lg border border-neza-line px-4 py-2.5 text-base uppercase shadow-sm @error('curp_consulta') border-rose-600 @enderror">
                            <p class="mt-2 text-sm leading-relaxed text-neza-muted">Tu CURP tiene 18 caracteres y se usará únicamente para dar seguimiento a este trámite.</p>
                            <p data-expediente-mensaje class="mt-2 hidden rounded-lg border px-3 py-2 text-xs" aria-live="polite"></p>
                            @error('curp_consulta')<p id="curp_consulta-error" class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div data-expediente-paso="2" hidden>
                            <label class="block text-base font-semibold">¿A nombre de quién realizarás el trámite? <span class="text-rose-700">*</span></label>
                            <p class="mt-1 text-sm text-neza-muted">Elige “persona física” si el trámite es para ti; “persona moral” si es para una empresa o asociación.</p>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <label class="flex min-h-16 cursor-pointer items-center gap-3 rounded-xl border border-neza-line bg-white px-4 py-3 text-base text-neza-ink shadow-sm has-[:checked]:border-neza-wine has-[:checked]:bg-neza-wine-soft"><input type="radio" name="tipo_persona" value="FISICA" @checked(old('tipo_persona') === 'FISICA') required data-datos-tipo-control class="size-5 border-neza-line text-neza-wine focus:ring-neza-wine"> <span><strong class="block">Persona física</strong><span class="text-sm text-neza-muted">Una persona</span></span></label>
                                <label class="flex min-h-16 cursor-pointer items-center gap-3 rounded-xl border border-neza-line bg-white px-4 py-3 text-base text-neza-ink shadow-sm has-[:checked]:border-neza-wine has-[:checked]:bg-neza-wine-soft"><input type="radio" name="tipo_persona" value="MORAL" @checked(old('tipo_persona') === 'MORAL') required data-datos-tipo-control class="size-5 border-neza-line text-neza-wine focus:ring-neza-wine"> <span><strong class="block">Persona moral</strong><span class="text-sm text-neza-muted">Empresa o asociación</span></span></label>
                            </div>
                            @error('tipo_persona')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div data-expediente-paso="3" hidden class="space-y-6">
                        <div class="rounded-xl border border-neza-line bg-neza-canvas p-4"><p class="font-bold text-neza-ink">Revisa tus datos antes de continuar</p><p class="mt-1 text-sm leading-relaxed text-neza-muted">Usaremos estos datos para comunicarnos contigo sobre tu trámite.</p></div>
                        <div><label for="email_contacto" class="block text-base font-semibold">Correo electrónico <span class="text-rose-700">*</span></label><p class="mt-1 text-sm text-neza-muted">Aquí recibirás avisos sobre tu solicitud.</p><input id="email_contacto" type="email" name="email_contacto" value="{{ old('email_contacto') }}" autocomplete="email" placeholder="nombre@correo.com" required @error('email_contacto') aria-invalid="true" aria-describedby="email_contacto-error" @enderror class="mt-2 min-h-12 w-full rounded-lg border border-neza-line px-4 py-2.5 text-base shadow-sm @error('email_contacto') border-rose-600 @enderror">@error('email_contacto')<p id="email_contacto-error" class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <div data-datos-tipo="FISICA" hidden class="grid gap-5 sm:grid-cols-2">
                            <div class="sm:col-span-2"><label for="nombres_solicitante" class="block text-base font-semibold">Nombre(s) <span class="text-rose-700">*</span></label><input id="nombres_solicitante" name="nombres_solicitante" value="{{ old('nombres_solicitante') }}" maxlength="100" autocomplete="given-name" required data-requerido-original="true" class="mt-2 min-h-12 w-full rounded-lg border border-neza-line px-4 py-2.5 text-base shadow-sm @error('nombres_solicitante') border-rose-600 @enderror">@error('nombres_solicitante')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                            <div><label for="apellido_paterno_solicitante" class="block text-base font-semibold">Apellido paterno <span class="text-rose-700">*</span></label><input id="apellido_paterno_solicitante" name="apellido_paterno_solicitante" value="{{ old('apellido_paterno_solicitante') }}" maxlength="100" autocomplete="family-name" required data-requerido-original="true" class="mt-2 min-h-12 w-full rounded-lg border border-neza-line px-4 py-2.5 text-base shadow-sm @error('apellido_paterno_solicitante') border-rose-600 @enderror">@error('apellido_paterno_solicitante')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                            <div><label for="apellido_materno_solicitante" class="block text-base font-semibold">Apellido materno <span class="text-rose-700">*</span></label><input id="apellido_materno_solicitante" name="apellido_materno_solicitante" value="{{ old('apellido_materno_solicitante') }}" maxlength="100" required data-requerido-original="true" class="mt-2 min-h-12 w-full rounded-lg border border-neza-line px-4 py-2.5 text-base shadow-sm @error('apellido_materno_solicitante') border-rose-600 @enderror">@error('apellido_materno_solicitante')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                            <div class="sm:col-span-2"><label for="telefono_contacto" class="block text-base font-semibold">Número telefónico <span class="text-rose-700">*</span></label><p class="mt-1 text-sm text-neza-muted">Escribe un número donde podamos localizarte.</p><input id="telefono_contacto" type="tel" name="telefono_contacto" value="{{ old('telefono_contacto') }}" maxlength="30" autocomplete="tel" inputmode="tel" required data-requerido-original="true" class="mt-2 min-h-12 w-full rounded-lg border border-neza-line px-4 py-2.5 text-base shadow-sm @error('telefono_contacto') border-rose-600 @enderror">@error('telefono_contacto')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        </div>
                        <div data-datos-tipo="MORAL" hidden class="grid gap-5 sm:grid-cols-2"><div class="sm:col-span-2"><label for="razon_social" class="block text-sm font-semibold">Razón social <span class="text-rose-700">*</span></label><input id="razon_social" name="razon_social" value="{{ old('razon_social') }}" maxlength="200" required data-requerido-original="true" class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error('razon_social') border-rose-600 @enderror">@error('razon_social')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div><div><label for="rfc" class="block text-sm font-semibold">RFC <span class="text-rose-700">*</span></label><input id="rfc" name="rfc" value="{{ old('rfc') }}" maxlength="13" required data-requerido-original="true" class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm uppercase @error('rfc') border-rose-600 @enderror">@error('rfc')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div></div>
                        <div class="grid gap-5 sm:grid-cols-2"><div class="sm:col-span-2"><label for="calle" class="block text-sm font-semibold">Calle <span class="text-rose-700">*</span></label><input id="calle" name="calle" value="{{ old('calle') }}" maxlength="200" autocomplete="street-address" required class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error('calle') border-rose-600 @enderror">@error('calle')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div><div><label for="numero_exterior" class="block text-sm font-semibold">Número exterior <span class="text-rose-700">*</span></label><input id="numero_exterior" name="numero_exterior" value="{{ old('numero_exterior') }}" maxlength="30" inputmode="text" required class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error('numero_exterior') border-rose-600 @enderror">@error('numero_exterior')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div><div><label for="colonia" class="block text-sm font-semibold">Colonia <span class="text-rose-700">*</span></label><input id="colonia" name="colonia" value="{{ old('colonia') }}" maxlength="200" autocomplete="address-level3" required class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error('colonia') border-rose-600 @enderror">@error('colonia')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div><div><label for="codigo_postal" class="block text-sm font-semibold">Código postal <span class="text-rose-700">*</span></label><input id="codigo_postal" name="codigo_postal" value="{{ old('codigo_postal') }}" maxlength="5" inputmode="numeric" pattern="[0-9]{5}" autocomplete="postal-code" required class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error('codigo_postal') border-rose-600 @enderror">@error('codigo_postal')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div></div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div><label for="municipio_nombre" class="block text-sm font-semibold">Municipio</label><input type="hidden" name="municipio_id" value="715"><input id="municipio_nombre" value="Nezahualcóyotl" disabled class="mt-2 w-full rounded-lg border border-neza-line bg-neza-canvas px-3 py-2.5 text-sm text-neza-muted">@error('municipio_id')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                            <div><label for="regimen_fiscal_externo_id" class="block text-sm font-semibold">Régimen fiscal <span class="text-rose-700">*</span></label><select id="regimen_fiscal_externo_id" name="regimen_fiscal_externo_id" required class="mt-2 w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm @error('regimen_fiscal_externo_id') border-rose-600 @enderror"><option value="">Selecciona primero el tipo de persona</option></select>@error('regimen_fiscal_externo_id')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        </div>
                        </div>
                    </fieldset>
                    @forelse ($secciones as $seccion)
                        <fieldset id="seccion-{{ $loop->iteration }}" data-formulario-seccion class="space-y-5 border-t border-neza-line pt-6 first:border-0 first:pt-0">
                            <legend class="flex flex-wrap items-center gap-3 text-xl font-bold">{{ $seccion->titulo }} <span class="rounded-full bg-neza-wine-soft px-3 py-1 text-xs font-bold text-neza-wine">Sección {{ $loop->iteration + 1 }} de {{ $totalSecciones + 1 }}</span></legend>
                            @if ($seccion->descripcion)<p class="text-base leading-relaxed text-neza-muted">{{ $seccion->descripcion }}</p>@endif
                            @foreach ($seccion->campos->sortBy('orden') as $campo)
                                @php($condiciones = $campo->condicionesDestino->map(fn ($condicion) => ['origen' => $condicion->campo_origen_id, 'operador' => $condicion->operador, 'valor' => data_get($condicion->valor_comparacion, 'valor'), 'accion' => $condicion->accion]))
                                @php($condicionesGrupo = $campo->gruposCampos->flatMap->condicionesDestino->map(fn ($condicion) => ['origen' => $condicion->campo_origen_id, 'operador' => $condicion->operador, 'valor' => data_get($condicion->valor_comparacion, 'valor'), 'accion' => $condicion->accion]))
                                <div data-campo-destino="{{ $campo->id }}" data-requerido-inicial="{{ $campo->requerido ? 'true' : 'false' }}" data-condiciones='@json(array_values(array_merge($condiciones->all(), $condicionesGrupo->all())))'>
                                    @foreach ($campo->condicionesDestino as $condicion)
                                        <span hidden data-condicion data-origen="{{ $condicion->campo_origen_id }}" data-operador="{{ $condicion->operador }}" data-valor="{{ data_get($condicion->valor_comparacion, 'valor') }}" data-accion="{{ $condicion->accion }}"></span>
                                    @endforeach
                                    <label for="campo-{{ $campo->id }}" class="block text-base font-semibold text-neza-ink">{{ $campo->etiqueta }} @if($campo->requerido)<span class="text-rose-700">*</span>@endif</label>
                                    @if (in_array($campo->tipo, ['RADIO', 'CHECKBOX'], true)) @error('respuestas.'.$campo->id)<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror @endif
                                    @if (data_get($campo->configuracion, 'texto_legal'))<p class="mt-2 text-sm leading-relaxed text-neza-muted">{{ data_get($campo->configuracion, 'texto_legal') }}</p>@endif
                                    @if (in_array($campo->tipo, ['TEXTAREA'], true))
                                        <textarea id="campo-{{ $campo->id }}" data-campo-control="{{ $campo->id }}" name="respuestas[{{ $campo->id }}]" rows="4" @required($campo->requerido) @error('respuestas.'.$campo->id) aria-invalid="true" @enderror class="mt-2 w-full rounded-lg border border-neza-line px-4 py-3 text-base shadow-sm @error('respuestas.'.$campo->id) border-rose-600 @enderror">{{ old('respuestas.'.$campo->id) }}</textarea>
                                    @elseif ($campo->tipo === 'DOMICILIO')
                                        <div class="mt-3 rounded-xl border border-neza-line bg-neza-canvas p-4" data-domicilio data-indice-url="{{ request()->getBaseUrl() }}/data/capa.json" data-coordenadas-url="{{ request()->getBaseUrl() }}/data/coordenadas.json">
                                        <p class="text-sm font-bold text-neza-ink">Completa tu domicilio en este orden</p>
                                        <p class="mt-1 text-sm leading-relaxed text-neza-muted">Escribe y selecciona tu colonia de la lista. El código postal y las calles disponibles se llenarán automáticamente.</p>
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            @foreach (config('formulario.campos_domicilio') as $clave => $subcampo)
                                                <div @class(['sm:col-span-2' => $clave === 'calle'])>
                                                    <label for="campo-{{ $campo->id }}-{{ $clave }}" class="block text-sm font-semibold text-neza-ink">{{ $subcampo['etiqueta'] }}</label>
                                                    <input id="campo-{{ $campo->id }}-{{ $clave }}" data-campo-control="{{ $campo->id }}" data-domicilio-campo="{{ $clave }}" name="respuestas[{{ $campo->id }}][{{ $clave }}]" type="{{ $subcampo['tipo'] }}" autocomplete="{{ $subcampo['autocomplete'] }}" value="{{ old('respuestas.'.$campo->id.'.'.$clave, $subcampo['valor'] ?? '') }}" @if (isset($subcampo['inputmode'])) inputmode="{{ $subcampo['inputmode'] }}" @endif @if (isset($subcampo['pattern'])) pattern="{{ $subcampo['pattern'] }}" @endif @if (in_array($clave, ['colonia', 'calle', 'numero'], true)) list="campo-{{ $campo->id }}-{{ $clave }}-opciones" @endif @readonly(($subcampo['disabled'] ?? false) || $clave === 'codigo_postal') @required($campo->requerido && ! ($subcampo['disabled'] ?? false)) class="mt-1 min-h-12 w-full rounded-lg border border-neza-line bg-white px-4 py-2.5 text-base shadow-sm read-only:bg-neza-canvas read-only:text-neza-muted">
                                                </div>
                                            @endforeach
                                        </div>
                                        <datalist id="campo-{{ $campo->id }}-colonia-opciones" data-domicilio-colonias></datalist><datalist id="campo-{{ $campo->id }}-calle-opciones" data-domicilio-calles></datalist><datalist id="campo-{{ $campo->id }}-numero-opciones" data-domicilio-numeros></datalist>
                                        <input type="hidden" name="respuestas[{{ $campo->id }}][latitud]" data-domicilio-latitud><input type="hidden" name="respuestas[{{ $campo->id }}][longitud]" data-domicilio-longitud><p class="mt-3 rounded-lg border border-neza-line bg-white px-3 py-2 text-sm text-neza-muted" data-domicilio-mensaje aria-live="polite">Selecciona una colonia para ver las calles disponibles.</p>
                                        </div>
                                    @elseif ($campo->tipo === 'DOMICILIO_FUERA_NEZA')
                                        <div class="mt-2 grid gap-3 sm:grid-cols-2" @error('respuestas.'.$campo->id) aria-invalid="true" @enderror>
                                            @foreach (config('formulario.campos_domicilio_fuera_neza') as $clave => $subcampo)
                                                <div @class(['sm:col-span-2' => in_array($clave, ['municipio', 'calle'], true)])>
                                                    <label for="campo-{{ $campo->id }}-{{ $clave }}" class="block text-xs font-semibold text-neza-muted">{{ $subcampo['etiqueta'] }}</label>
                                                    <input id="campo-{{ $campo->id }}-{{ $clave }}" data-campo-control="{{ $campo->id }}" name="respuestas[{{ $campo->id }}][{{ $clave }}]" type="{{ $subcampo['tipo'] }}" maxlength="{{ $subcampo['maxlength'] }}" autocomplete="{{ $subcampo['autocomplete'] }}" value="{{ old('respuestas.'.$campo->id.'.'.$clave) }}" @if (isset($subcampo['inputmode'])) inputmode="{{ $subcampo['inputmode'] }}" @endif @if (isset($subcampo['pattern'])) pattern="{{ $subcampo['pattern'] }}" @endif @required($campo->requerido) @error('respuestas.'.$campo->id) aria-invalid="true" @enderror class="mt-1 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error('respuestas.'.$campo->id) border-rose-600 @enderror">
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif ($campo->tipo === 'DOMICILIO_USA')
                                        <div class="mt-2 grid gap-3 sm:grid-cols-2" @error('respuestas.'.$campo->id) aria-invalid="true" @enderror>
                                            @foreach (config('formulario.campos_domicilio_usa') as $clave => $subcampo)
                                                @php($requeridoSubcampo = $campo->requerido && ($subcampo['requerido'] ?? true))
                                                <div @class(['sm:col-span-2' => $clave === 'calle'])>
                                                    <label for="campo-{{ $campo->id }}-{{ $clave }}" class="block text-xs font-semibold text-neza-muted">{{ $subcampo['etiqueta'] }}</label>
                                                    @if ($clave === 'estado')
                                                        <select id="campo-{{ $campo->id }}-{{ $clave }}" data-campo-control="{{ $campo->id }}" name="respuestas[{{ $campo->id }}][{{ $clave }}]" @required($requeridoSubcampo) @error('respuestas.'.$campo->id) aria-invalid="true" @enderror class="mt-1 w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm @error('respuestas.'.$campo->id) border-rose-600 @enderror">
                                                            <option value="">Selecciona un estado</option>
                                                            @foreach (config('formulario.estados_usa') as $codigo => $nombre)
                                                                <option value="{{ $codigo }}" @selected(old('respuestas.'.$campo->id.'.estado') === $codigo)>{{ $nombre }}</option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        <input id="campo-{{ $campo->id }}-{{ $clave }}" data-campo-control="{{ $campo->id }}" name="respuestas[{{ $campo->id }}][{{ $clave }}]" type="{{ $subcampo['tipo'] }}" maxlength="{{ $subcampo['maxlength'] }}" autocomplete="{{ $subcampo['autocomplete'] }}" value="{{ old('respuestas.'.$campo->id.'.'.$clave) }}" @if (isset($subcampo['inputmode'])) inputmode="{{ $subcampo['inputmode'] }}" @endif @if (isset($subcampo['pattern'])) pattern="{{ $subcampo['pattern'] }}" @endif @required($requeridoSubcampo) @error('respuestas.'.$campo->id) aria-invalid="true" @enderror class="mt-1 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error('respuestas.'.$campo->id) border-rose-600 @enderror">
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                        <p class="mt-2 text-xs text-neza-muted">País: Estados Unidos. Captura la dirección, ciudad, estado y código postal (ZIP).</p>
                                    @elseif ($campo->tipo === 'ESTADO')
                                        <select id="campo-{{ $campo->id }}" data-campo-control="{{ $campo->id }}" name="respuestas[{{ $campo->id }}]" @required($campo->requerido) @error('respuestas.'.$campo->id) aria-invalid="true" @enderror class="mt-2 min-h-12 w-full rounded-lg border border-neza-line bg-white px-4 py-2.5 text-base shadow-sm @error('respuestas.'.$campo->id) border-rose-600 @enderror"><option value="">Selecciona una entidad</option>@foreach (config('formulario.entidades_federativas') as $clave => $entidad)<option value="{{ $clave }}" data-valor-condicional="{{ $entidad }}" @selected(old('respuestas.'.$campo->id) === $clave)>{{ $entidad }}</option>@endforeach</select>
                                    @elseif (in_array($campo->tipo, ['SELECT', 'MULTISELECT'], true))
                                        <select id="campo-{{ $campo->id }}" data-campo-control="{{ $campo->id }}" name="respuestas[{{ $campo->id }}]{{ $campo->tipo === 'MULTISELECT' ? '[]' : '' }}" @if($campo->tipo === 'MULTISELECT') multiple @endif @required($campo->requerido) @error('respuestas.'.$campo->id) aria-invalid="true" @enderror class="mt-2 min-h-12 w-full rounded-lg border border-neza-line bg-white px-4 py-2.5 text-base shadow-sm @error('respuestas.'.$campo->id) border-rose-600 @enderror"><option value="">Selecciona una opción</option>@foreach($campo->opciones->where('activo', true)->sortBy('orden') as $opcion)<option value="{{ $opcion->valor }}" data-valor-condicional="{{ $opcion->etiqueta }}" @selected(in_array($opcion->valor, (array) old('respuestas.'.$campo->id, []), true))>{{ $opcion->etiqueta }}</option>@endforeach</select>
                                    @elseif ($campo->tipo === 'RADIO')
                                        <div class="mt-2 space-y-2" @error('respuestas.'.$campo->id) aria-invalid="true" @enderror>@foreach($campo->opciones->where('activo', true)->sortBy('orden') as $opcion)<label class="flex min-h-12 items-center gap-3 rounded-lg border border-neza-line px-4 text-base text-neza-ink has-[:checked]:border-neza-wine has-[:checked]:bg-neza-wine-soft"><input data-campo-control="{{ $campo->id }}" type="radio" name="respuestas[{{ $campo->id }}]" value="{{ $opcion->valor }}" data-valor-condicional="{{ $opcion->etiqueta }}" @checked(old('respuestas.'.$campo->id) == $opcion->valor) @required($campo->requerido) class="size-5 border-neza-line text-neza-wine focus:ring-neza-wine">{{ $opcion->etiqueta }}</label>@endforeach</div>
                                    @elseif ($campo->tipo === 'CHECKBOX')
                                        <label class="mt-2 flex items-center gap-2 text-sm text-neza-ink" @error('respuestas.'.$campo->id) aria-invalid="true" @enderror><input id="campo-{{ $campo->id }}" data-campo-control="{{ $campo->id }}" type="checkbox" name="respuestas[{{ $campo->id }}]" value="1" @checked(old('respuestas.'.$campo->id)) @required($campo->requerido) class="size-4 rounded border-neza-line text-neza-wine focus:ring-neza-wine">Seleccionar</label>
                                    @elseif ($campo->tipo === 'FILE')
                                        <input id="campo-{{ $campo->id }}" data-campo-control="{{ $campo->id }}" data-file-input data-max-bytes="10485760" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" name="respuestas[{{ $campo->id }}]" type="file" @required($campo->requerido) @error('respuestas.'.$campo->id) aria-invalid="true" @enderror class="mt-2 block min-h-12 w-full rounded-lg border border-neza-line bg-white px-3 py-2 text-base text-neza-muted file:mr-4 file:rounded-md file:border-0 file:bg-neza-wine-soft file:px-3 file:py-2 file:text-sm file:font-bold file:text-neza-wine @error('respuestas.'.$campo->id) border-rose-600 @enderror">
                                        <p class="mt-2 text-xs text-neza-muted">PDF, imágenes o documentos permitidos. Tamaño máximo: 10 MB.</p>
                                    @elseif ($campo->tipo === 'MAPA')
                                        <div class="mt-2 space-y-3" data-mapa-ubicacion data-domicilio-origen="{{ data_get($campo->configuracion, 'domicilio_origen_id') }}">
                                            <div class="flex flex-wrap items-center gap-3"><button type="button" data-mapa-ubicar class="rounded-lg border border-neza-wine px-3 py-2 text-sm font-semibold text-neza-wine hover:bg-neza-wine-soft">Ubicar domicilio</button><span data-mapa-mensaje class="text-xs text-neza-muted">Usa el botón para buscar el domicilio o coloca el punto manualmente.</span></div>
                                            <div data-mapa-lienzo class="h-80 rounded-lg border border-neza-line" aria-label="Mapa para seleccionar la ubicación del predio"></div>
                                            <input type="hidden" data-campo-control="{{ $campo->id }}" name="respuestas[{{ $campo->id }}][latitud]" value="{{ old('respuestas.'.$campo->id.'.latitud') }}">
                                            <input type="hidden" name="respuestas[{{ $campo->id }}][longitud]" value="{{ old('respuestas.'.$campo->id.'.longitud') }}">
                                        </div>
                                    @else
                                        @php($fechaBloqueada = $campo->tipo === 'DATE' && data_get($campo->configuracion, 'fecha_actual_bloqueada'))
                                        <input id="campo-{{ $campo->id }}" data-campo-control="{{ $campo->id }}" name="respuestas[{{ $campo->id }}]" value="{{ $fechaBloqueada ? now()->format('d/m/Y') : old('respuestas.'.$campo->id) }}" type="{{ $campo->tipo === 'EMAIL' ? 'email' : ($campo->tipo === 'NUMBER' ? 'number' : 'text') }}" @disabled($fechaBloqueada) @if($campo->tipo === 'DATE') inputmode="numeric" autocomplete="off" placeholder="dd/mm/aaaa" pattern="[0-9]{2}/[0-9]{2}/[0-9]{4}" data-fecha-mexico @endif @if($campo->tipo === 'CURP') pattern="[A-Za-z]{4}[0-9]{6}[A-Za-z]{6}[A-Za-z0-9][0-9]" @endif @required($campo->requerido && ! $fechaBloqueada) @error('respuestas.'.$campo->id) aria-invalid="true" @enderror class="mt-2 min-h-12 w-full rounded-lg border border-neza-line px-4 py-2.5 text-base shadow-sm disabled:bg-neza-canvas @error('respuestas.'.$campo->id) border-rose-600 @enderror">
                                    @endif
                                    @unless (in_array($campo->tipo, ['RADIO', 'CHECKBOX'], true)) @error('respuestas.'.$campo->id)<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror @endunless
                                </div>
                            @endforeach
                        </fieldset>
                    @empty
                        <p class="rounded-xl border border-dashed border-neza-line p-5 text-sm text-neza-muted">El formulario todavía no contiene campos configurados.</p>
                    @endforelse
                    @if ($errors->has('formulario_version_id'))
                        <p class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $errors->first('formulario_version_id') }}</p>
                    @endif
                    <fieldset data-formulario-privacidad class="rounded-xl border border-neza-line bg-neza-canvas p-5">
                        <legend class="px-2 text-base font-bold text-neza-ink">Antes de enviar</legend>
                        <p class="mb-4 text-sm leading-relaxed text-neza-muted">Lee y acepta el aviso para que podamos atender tu solicitud.</p>
                        <label class="flex min-h-12 items-start gap-3 text-base leading-relaxed text-neza-muted">
                            <input type="checkbox" name="acepto_aviso_privacidad" value="1" required @checked(old('acepto_aviso_privacidad')) class="mt-1 size-5 shrink-0 rounded border-neza-line text-neza-wine focus:ring-neza-wine">
                            <span>Acepto el <a href="{{ route('portal.aviso-privacidad') }}" target="_blank" rel="noopener" class="font-semibold text-neza-wine underline underline-offset-2">aviso de privacidad</a> y el tratamiento de mis datos personales para atender este trámite.</span>
                        </label>
                        @error('acepto_aviso_privacidad')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </fieldset>
                    <div class="flex flex-col-reverse gap-3 border-t border-neza-line pt-6 sm:flex-row sm:justify-between">
                        <button type="button" data-seccion-anterior hidden class="min-h-12 rounded-xl border border-neza-line px-6 text-base font-bold text-neza-wine focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Regresar</button>
                        <div class="flex flex-col gap-3 sm:ml-auto sm:flex-row">
                            <button type="button" data-seccion-siguiente hidden class="min-h-12 rounded-xl bg-neza-wine px-6 text-base font-bold text-white shadow-sm hover:bg-neza-wine/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Continuar</button>
                            <button type="submit" data-seccion-enviar class="min-h-12 rounded-xl bg-neza-wine px-6 text-base font-bold text-white shadow-sm hover:bg-neza-wine/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Enviar solicitud</button>
                        </div>
                    </div>
                </form>
                <div data-tesoreria-loader hidden class="fixed inset-0 z-[60] grid place-items-center bg-black/60 p-4" role="status" aria-live="assertive" aria-label="Consultando información">
                    <div class="flex flex-col items-center rounded-2xl bg-white px-8 py-7 text-center shadow-xl">
                        <span class="loader" aria-hidden="true"></span>
                        <p class="mt-4 text-base font-bold text-neza-ink">Estamos revisando tus datos</p>
                        <p class="mt-1 text-sm text-neza-muted">Esto puede tardar unos segundos.</p>
                    </div>
                </div>
                <div data-expediente-identidad-modal hidden class="fixed inset-0 z-50 grid place-items-center bg-neza-ink/50 p-4" role="dialog" aria-modal="true" aria-labelledby="expediente-identidad-titulo">
                    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <p class="text-xs font-bold uppercase tracking-wide text-neza-wine">Datos encontrados</p>
                        <h2 id="expediente-identidad-titulo" class="mt-2 text-xl font-bold text-neza-ink">¿Eres esta persona?</h2>
                        <p data-expediente-identidad-nombre class="mt-4 text-lg font-semibold text-neza-ink"></p>
                        <p class="mt-3 text-sm leading-relaxed text-neza-muted">Para proteger tus datos, enviaremos un código al correo registrado si confirmas tu identidad.</p>
                        <p data-expediente-identidad-error class="mt-3 text-sm text-rose-700" role="alert" hidden></p>
                        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button type="button" data-expediente-identidad-no class="h-11 rounded-xl border border-neza-line px-4 text-sm font-bold text-neza-wine focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">No soy yo</button>
                            <button type="button" data-expediente-identidad-si class="h-11 rounded-xl bg-neza-wine px-4 text-sm font-bold text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Sí, enviar código</button>
                        </div>
                    </div>
                </div>
                <div data-expediente-token-modal hidden class="fixed inset-0 z-50 grid place-items-center bg-neza-ink/50 p-4" role="dialog" aria-modal="true" aria-labelledby="expediente-token-titulo">
                    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <p class="text-xs font-bold uppercase tracking-wide text-neza-wine">Verifica tu identidad</p>
                        <h2 id="expediente-token-titulo" class="mt-2 text-xl font-bold text-neza-ink">Ingresa tu código</h2>
                        <p class="mt-3 text-sm leading-relaxed text-neza-muted">Enviamos un código de seis dígitos al correo registrado.</p>
                        <label for="expediente_token" class="mt-5 block text-sm font-semibold text-neza-ink">Código de verificación</label>
                        <input id="expediente_token" data-expediente-token-input inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm tracking-[0.3em] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                        <p data-expediente-token-error class="mt-3 text-sm text-rose-700" role="alert" hidden></p>
                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" data-expediente-token-cancelar class="h-11 rounded-xl border border-neza-line px-4 text-sm font-bold text-neza-wine focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Cancelar</button>
                            <button type="button" data-expediente-token-verificar class="h-11 rounded-xl bg-neza-wine px-4 text-sm font-bold text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Verificar código</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[aria-label^="Formulario"]');
    if (!form) return;
    if (form.dataset.asistenteSeccionesInicializado === 'true') return;
    form.dataset.asistenteSeccionesInicializado = 'true';

    ['nombres_solicitante', 'apellido_paterno_solicitante', 'apellido_materno_solicitante', 'razon_social'].forEach((name) => { form.elements[name].minLength = 2; });
    form.elements.curp_consulta.pattern = '[A-Za-z]{4}[0-9]{6}[A-Za-z]{6}[A-Za-z0-9][0-9]';
    form.elements.telefono_contacto.pattern = '[0-9+()\\s-]{7,30}';
    form.elements.rfc.pattern = '[A-Za-z&Ññ]{3,4}[0-9]{6}[A-Za-z0-9]{3}';
    form.querySelectorAll('[data-fecha-mexico]').forEach((campo) => {
        campo.addEventListener('input', () => {
            const numeros = campo.value.replace(/\D/g, '').slice(0, 8);
            const partes = [numeros.slice(0, 2), numeros.slice(2, 4), numeros.slice(4, 8)].filter(Boolean);
            campo.value = partes.join('/');
        });
    });
    const gruposTipo = [...form.querySelectorAll('[data-datos-tipo]')];
    const regimenesFiscales = @json($regimenesFiscales);
    const regimenFiscal = form.elements.regimen_fiscal_externo_id;
    const actualizarRegimenesFiscales = () => {
        const tipo = form.querySelector('input[name="tipo_persona"]:checked')?.value ?? '';
        const previo = '{{ old('regimen_fiscal_externo_id') }}';
        regimenFiscal.innerHTML = '<option value="">Selecciona un régimen fiscal</option>';
        (regimenesFiscales[tipo] ?? []).forEach((regimen) => {
            const opcion = document.createElement('option');
            opcion.value = regimen.id;
            opcion.textContent = regimen.name;
            opcion.selected = String(regimen.id) === previo;
            regimenFiscal.appendChild(opcion);
        });
        regimenFiscal.disabled = !tipo || !(regimenesFiscales[tipo] ?? []).length;
    };
    const aplicarTipoPersona = () => {
        const tipo = form.querySelector('input[name="tipo_persona"]:checked')?.value ?? '';
        gruposTipo.forEach((grupo) => {
            const visible = grupo.dataset.datosTipo === tipo;
            grupo.hidden = !visible;
            grupo.querySelectorAll('input, select').forEach((input) => {
                input.required = visible && input.dataset.requeridoOriginal === 'true';
                input.disabled = !visible;
            });
        });
    };
    const curpExpediente = form.querySelector('[data-expediente-curp]');
    const mensajeExpediente = form.querySelector('[data-expediente-mensaje]');
    const modalIdentidad = form.parentElement.querySelector('[data-expediente-identidad-modal]');
    const modalToken = form.parentElement.querySelector('[data-expediente-token-modal]');
    const nombreIdentidad = form.parentElement.querySelector('[data-expediente-identidad-nombre]');
    const errorIdentidad = form.parentElement.querySelector('[data-expediente-identidad-error]');
    const errorToken = form.parentElement.querySelector('[data-expediente-token-error]');
    const inputToken = form.parentElement.querySelector('[data-expediente-token-input]');
    const botonIdentidadSi = form.parentElement.querySelector('[data-expediente-identidad-si]');
    const botonTokenVerificar = form.parentElement.querySelector('[data-expediente-token-verificar]');
    const loaderTesoreria = form.parentElement.querySelector('[data-tesoreria-loader]');
    let consultaExpediente = 0;
    const mostrarMensajeExpediente = (texto, existe) => {
        if (!mensajeExpediente) return;
        mensajeExpediente.textContent = texto;
        mensajeExpediente.className = 'mt-2 rounded-lg border px-3 py-2 text-xs ' + (existe ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-neza-line bg-neza-canvas text-neza-muted');
    };
    const cerrarModal = (modal) => { if (modal) modal.hidden = true; };
    const abrirModal = (modal, foco) => { if (modal) { modal.hidden = false; window.requestAnimationFrame(() => foco?.focus()); } };
    const mostrarErrorModal = (elemento, mensaje = '') => { if (!elemento) return; elemento.textContent = mensaje; elemento.hidden = mensaje === ''; };
    const mostrarCargaTesoreria = (mostrar) => { if (loaderTesoreria) loaderTesoreria.hidden = !mostrar; };
    const datosExpediente = () => Object.fromEntries([
        'curp_consulta', 'tipo_persona', 'rfc', 'email_contacto', 'telefono_contacto',
        'nombres_solicitante', 'apellido_paterno_solicitante', 'apellido_materno_solicitante',
        'razon_social', 'calle', 'numero_exterior', 'colonia', 'codigo_postal',
        'municipio_id', 'regimen_fiscal_externo_id',
    ].map((nombre) => [nombre, form.elements[nombre]?.value ?? '']));
    const solicitudJson = async (url, datos) => {
        const respuesta = await fetch(url, { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': form.dataset.csrfToken }, body: JSON.stringify(datos) });
        const contenido = await respuesta.json().catch(() => ({}));
        if (!respuesta.ok) throw new Error(contenido.message || contenido.errors?.token?.[0] || 'No fue posible completar la solicitud.');
        return contenido;
    };
    const verificarExpediente = async () => {
        const curp = (curpExpediente?.value ?? '').trim().toUpperCase();
        const tipo = form.querySelector('input[name="tipo_persona"]:checked')?.value;
        const curpValida = /^[A-Z]{4}[0-9]{6}[A-Z]{6}[A-Z0-9][0-9]$/.test(curp);
        if (!curpValida || !tipo || !curpExpediente?.dataset.expedienteUrl) return false;
        curpExpediente.value = curp;
        const consultaActual = ++consultaExpediente;
        mostrarCargaTesoreria(true);
        const url = new URL(curpExpediente.dataset.expedienteUrl, window.location.origin);
        url.searchParams.set('curp', curp);
        url.searchParams.set('tipo_persona', tipo);
        try {
            const respuestaHttp = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!respuestaHttp.ok) throw new Error();
            const respuesta = await respuestaHttp.json();
            if (consultaActual !== consultaExpediente) return false;
            if (!respuesta.existe) {
                mostrarMensajeExpediente('No encontramos un expediente previo. Completa tus datos para crear uno.', false);

                return true;
            }
            nombreIdentidad.textContent = respuesta.nombre;
            mostrarErrorModal(errorIdentidad);
            abrirModal(modalIdentidad, botonIdentidadSi);

            return false;
        } catch {
            if (consultaActual === consultaExpediente) mostrarMensajeExpediente('No fue posible comprobar el expediente. Intenta nuevamente para continuar.', false);

            return false;
        } finally {
            mostrarCargaTesoreria(false);
        }
    };
    botonIdentidadSi?.addEventListener('click', async () => {
        const tipo = form.querySelector('input[name="tipo_persona"]:checked')?.value;
        botonIdentidadSi.disabled = true;
        botonIdentidadSi.textContent = 'Enviando…';
        mostrarErrorModal(errorIdentidad);
        try {
            await solicitudJson(form.dataset.expedienteTokenUrl, { curp: curpExpediente.value, tipo_persona: tipo });
            cerrarModal(modalIdentidad);
            inputToken.value = '';
            mostrarErrorModal(errorToken);
            abrirModal(modalToken, inputToken);
        } catch (error) {
            mostrarErrorModal(errorIdentidad, error.message);
        } finally {
            botonIdentidadSi.disabled = false;
            botonIdentidadSi.textContent = 'Sí, enviar código';
        }
    });
    form.parentElement.querySelector('[data-expediente-identidad-no]')?.addEventListener('click', () => {
        cerrarModal(modalIdentidad);
        curpExpediente.value = '';
        mostrarMensajeExpediente('Verifica tu CURP para continuar.', false);
        mostrarPasoExpediente(1);
        curpExpediente.focus();
    });
    form.parentElement.querySelector('[data-expediente-token-cancelar]')?.addEventListener('click', () => cerrarModal(modalToken));
    botonTokenVerificar?.addEventListener('click', async () => {
        const token = inputToken.value.trim();
        if (!/^[0-9]{6}$/.test(token)) { mostrarErrorModal(errorToken, 'Ingresa los seis dígitos del código.'); inputToken.focus(); return; }
        botonTokenVerificar.disabled = true;
        botonTokenVerificar.textContent = 'Verificando…';
        mostrarErrorModal(errorToken);
        try {
            const respuesta = await solicitudJson(form.dataset.expedienteTokenVerificarUrl, { token });
            Object.entries(respuesta.datos).forEach(([nombre, valor]) => {
                const control = form.elements[nombre];
                if (control && valor !== null) control.value = valor;
            });
            cerrarModal(modalToken);
            mostrarMensajeExpediente('Confirmamos tu identidad. Revisamos y precargamos tus datos personales.', true);
            mostrarPasoExpediente(3);
        } catch (error) {
            mostrarErrorModal(errorToken, error.message);
        } finally {
            botonTokenVerificar.disabled = false;
            botonTokenVerificar.textContent = 'Verificar código';
        }
    });
    curpExpediente?.addEventListener('blur', verificarExpediente);
    form.querySelectorAll('input[name="tipo_persona"]').forEach((radio) => radio.addEventListener('change', aplicarTipoPersona));
    form.querySelectorAll('input[name="tipo_persona"]').forEach((radio) => radio.addEventListener('change', actualizarRegimenesFiscales));
    aplicarTipoPersona();
    actualizarRegimenesFiscales();
    form.querySelectorAll('[data-campo-destino] input, [data-campo-destino] select, [data-campo-destino] textarea').forEach((input) => {
        input.dataset.requeridoOriginal = input.required ? 'true' : 'false';
    });
    const valueOf = (id) => {
        const controls = [...form.querySelectorAll('[data-campo-control="' + id + '"]')];
        const source = controls[0] ?? document.getElementById('campo-' + id);
        if (!source) return '';
        if (source.type === 'radio') return controls.find((control) => control.checked)?.dataset.valorCondicional ?? '';
        if (source.tagName === 'SELECT') return [...source.selectedOptions].map((option) => option.dataset.valorCondicional ?? option.text).join(', ');
        return source.type === 'checkbox' ? (source.checked ? 'true' : '') : source.value;
    };
    const evaluate = (value, condition) => { const actual = String(value ?? '').trim().toLocaleLowerCase(); const expected = String(condition.valor ?? '').trim().toLocaleLowerCase(); return ({ EQUALS: actual === expected, NOT_EQUALS: actual !== expected, CONTAINS: actual.includes(expected), GREATER_THAN: Number(actual) > Number(expected), LESS_THAN: Number(actual) < Number(expected), IS_EMPTY: actual === '', IS_NOT_EMPTY: actual !== '' }[condition.operador] ?? false); };
    const refresh = () => form.querySelectorAll('[data-campo-destino]').forEach(function (container) {
        const conditions = JSON.parse(container.dataset.condiciones);
        const matches = (action) => conditions.filter((condition) => condition.accion === action).map((condition) => evaluate(valueOf(condition.origen), condition));
        const show = matches('SHOW'); const hide = matches('HIDE'); const require = matches('REQUIRE'); const optional = matches('OPTIONAL');
        const visible = (show.length === 0 || show.every(Boolean)) && !hide.some(Boolean);
        let required = container.dataset.requeridoInicial === 'true';
        if (require.length) required = require.every(Boolean);
        if (optional.some(Boolean)) required = false;
        container.hidden = !visible;
        container.querySelectorAll('input, select, textarea').forEach((input) => {
            const requeridoOriginal = input.dataset.requeridoOriginal === 'true';
            input.required = visible && required && (container.dataset.requeridoInicial !== 'true' || requeridoOriginal);
            input.disabled = !visible;
        });
    });
    form.addEventListener('change', refresh); form.addEventListener('input', refresh); refresh();
    const secciones = [...form.querySelectorAll('[data-formulario-seccion]')];
    const privacidad = form.querySelector('[data-formulario-privacidad]');
    const anterior = form.querySelector('[data-seccion-anterior]');
    const siguiente = form.querySelector('[data-seccion-siguiente]');
    const enviar = form.querySelector('[data-seccion-enviar]');
    const progresoTexto = document.querySelector('[data-progreso-texto]');
    const progresoBarra = document.querySelector('[data-progreso-barra]');
    const pasosProgreso = [...document.querySelectorAll('[data-progreso-paso]')];
    const pasosExpediente = [...form.querySelectorAll('[data-expediente-paso]')];
    let pasoExpediente = 1;
    const mostrarPasoExpediente = (paso) => {
        pasoExpediente = paso;
        pasosExpediente.forEach((contenedor) => { contenedor.hidden = Number(contenedor.dataset.expedientePaso) !== paso; });
        if (actual === 0) {
            anterior.hidden = paso === 1;
            siguiente.textContent = paso === 3 ? 'Continuar al trámite' : 'Continuar';
        }
    };
    const primeraSeccionConError = secciones.findIndex((seccion) => seccion.querySelector('[aria-invalid="true"], p.text-rose-700'));
    let actual = primeraSeccionConError >= 0 ? primeraSeccionConError : (privacidad.querySelector('p.text-rose-700') ? secciones.length - 1 : 0);
    const actualizarProgreso = () => {
        const paso = actual + 1;
        const total = secciones.length;
        if (progresoTexto) progresoTexto.textContent = `Paso ${paso} de ${total}`;
        if (progresoBarra) {
            progresoBarra.setAttribute('aria-valuenow', String(paso));
        }
        pasosProgreso.forEach((elemento, indice) => {
            const activo = indice === actual;
            elemento.toggleAttribute('aria-current', activo);
            elemento.classList.toggle('bg-white', activo);
            elemento.classList.toggle('text-neza-muted', !activo);
            elemento.querySelector('span')?.classList.toggle('bg-neza-wine', activo);
            elemento.querySelector('span')?.classList.toggle('text-white', activo);
        });
    };
    const mostrar = (indice, enfocar = true) => {
        actual = indice;
        secciones.forEach((seccion, i) => { seccion.hidden = i !== actual; });
        privacidad.hidden = actual !== secciones.length - 1;
        anterior.hidden = actual === 0 && pasoExpediente === 1;
        siguiente.hidden = actual === secciones.length - 1;
        enviar.hidden = actual !== secciones.length - 1;
        siguiente.textContent = actual === 0 && pasoExpediente === 3 ? 'Continuar al trámite' : 'Siguiente';
        actualizarProgreso();
        if (enfocar) {
            const titulo = secciones[actual].querySelector('legend');
            titulo.setAttribute('tabindex', '-1');
            titulo.focus();
            titulo.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };
    const mostrarError = (input) => {
        let mensaje = input.parentElement.querySelector('[data-error-validacion]');
        if (!mensaje) {
            mensaje = document.createElement('p');
            mensaje.dataset.errorValidacion = '';
            mensaje.className = 'mt-2 text-sm text-rose-700';
            mensaje.setAttribute('role', 'alert');
            input.insertAdjacentElement('afterend', mensaje);
        }
        mensaje.textContent = input.validationMessage;
        input.setAttribute('aria-invalid', 'true');
    };
    form.addEventListener('input', (event) => {
        const input = event.target;
        if (!input.matches('input, select, textarea')) return;
        const mensaje = input.parentElement.querySelector('[data-error-validacion]');
        if (mensaje && input.validity.valid) { mensaje.remove(); input.removeAttribute('aria-invalid'); }
    });
    let avanzandoSeccion = false;
    siguiente.addEventListener('click', async (event) => {
        event.preventDefault();
        event.stopPropagation();
        if (avanzandoSeccion) return;

        avanzandoSeccion = true;
        const seccionActual = actual;
        const controles = seccionActual === 0
            ? pasosExpediente.find((contenedor) => Number(contenedor.dataset.expedientePaso) === pasoExpediente)?.querySelectorAll('input, select, textarea') ?? []
            : secciones[seccionActual].querySelectorAll('input, select, textarea');
        const invalido = [...controles].find((input) => input.willValidate && !input.checkValidity());
        if (invalido) {
            mostrarError(invalido);
            invalido.focus();
            avanzandoSeccion = false;

            return;
        }

        if (seccionActual === 0 && pasoExpediente === 3) {
            siguiente.disabled = true;
            siguiente.textContent = 'Actualizando datos…';
            mostrarCargaTesoreria(true);
            try {
                const respuesta = await solicitudJson(form.dataset.expedienteSincronizarUrl, datosExpediente());
                if (!respuesta.sincronizado) mostrarMensajeExpediente('Guardamos tus datos. Tesorería se actualizará automáticamente en breve.', false);
                mostrar(seccionActual + 1);
            } catch (error) {
                mostrarMensajeExpediente(error.message);
            } finally {
                mostrarCargaTesoreria(false);
                siguiente.disabled = false;
                siguiente.textContent = 'Siguiente';
                avanzandoSeccion = false;
            }

            return;
        }

        if (seccionActual === 0 && pasoExpediente < 3) {
            if (pasoExpediente === 2) {
                siguiente.disabled = true;
                const puedeContinuar = await verificarExpediente();
                siguiente.disabled = false;
                siguiente.textContent = 'Continuar';
                if (puedeContinuar) mostrarPasoExpediente(3);
                avanzandoSeccion = false;

                return;
            }
            mostrarPasoExpediente(pasoExpediente + 1);
            window.requestAnimationFrame(() => { avanzandoSeccion = false; });

            return;
        }

        mostrar(seccionActual + 1);
        window.requestAnimationFrame(() => { avanzandoSeccion = false; });
    });
    anterior.addEventListener('click', () => {
        if (actual === 0 && pasoExpediente > 1) {
            mostrarPasoExpediente(pasoExpediente - 1);

            return;
        }
        mostrar(actual - 1);
    });
    form.addEventListener('invalid', (event) => {
        const indice = secciones.findIndex((seccion) => seccion.contains(event.target));
        const pasoConError = pasosExpediente.find((contenedor) => contenedor.contains(event.target));
        if (pasoConError) mostrarPasoExpediente(Number(pasoConError.dataset.expedientePaso));
        if (indice >= 0 && indice !== actual) mostrar(indice, false);
        mostrarError(event.target);
    }, true);
    mostrarPasoExpediente(pasosExpediente.find((contenedor) => contenedor.querySelector('[aria-invalid="true"], p.text-rose-700')) ? Number(pasosExpediente.find((contenedor) => contenedor.querySelector('[aria-invalid="true"], p.text-rose-700')).dataset.expedientePaso) : 1);
    mostrar(actual, false);
    const primerError = secciones[actual].querySelector('[aria-invalid="true"]');
    if (primerError && typeof primerError.focus === 'function') { primerError.focus(); primerError.scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
    const resumen = document.getElementById('resumen-errores');
    if (resumen) { resumen.setAttribute('tabindex', '-1'); resumen.focus(); }
});
</script>
@endsection
