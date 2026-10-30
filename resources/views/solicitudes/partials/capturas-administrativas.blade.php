@foreach ($capturasAdministrativas as $captura)
    @php($esActaNacimiento = $captura->clave === 'datos_acta_nacimiento')
    <section class="overflow-hidden rounded-xl border border-neza-line bg-white shadow-sm" aria-labelledby="captura-{{ $captura->id }}-titulo">
        <div class="flex flex-col gap-3 border-b border-neza-line px-5 py-5 sm:flex-row sm:items-start sm:justify-between sm:px-6">
            <div>
                <h2 id="captura-{{ $captura->id }}-titulo" class="text-lg font-bold text-neza-ink">{{ $captura->titulo }}</h2>
                @if ($captura->descripcion)<p class="mt-1 text-sm text-neza-muted">{{ $captura->descripcion }}</p>@endif
            </div>
            <span @class(['w-fit rounded-full px-3 py-1 text-xs font-bold', 'bg-amber-100 text-amber-900' => $captura->incompleta, 'bg-emerald-100 text-emerald-800' => ! $captura->incompleta])>{{ $captura->incompleta ? 'Pendiente' : 'Completa' }}</span>
        </div>
        <form action="{{ route('solicitudes.capturas-administrativas.guardar', [$solicitud, $captura->id]) }}" method="POST" class="space-y-5 p-5 sm:p-6" data-disable-on-submit>
            @csrf
            @method('PUT')
            @if ($esActaNacimiento && $captura->campo_fuente_id)
                <div class="flex flex-col gap-3 rounded-lg border border-neza-wine/20 bg-neza-wine-soft/40 p-4 sm:flex-row sm:items-center sm:justify-between" data-acta-ocr data-acta-ocr-url="{{ route('solicitudes.acta-ocr', $solicitud) }}" data-acta-ocr-token="{{ csrf_token() }}">
                    <div>
                        <p class="text-sm font-semibold text-neza-ink">Captura asistida</p>
                        <p class="mt-0.5 text-sm text-neza-muted">Extrae los datos del acta de nacimiento adjunta con OCR; los campos quedarán resaltados para que los verifiques uno por uno.</p>
                    </div>
                    <button type="button" data-acta-ocr-btn data-acta-ocr-panel="captura-{{ $captura->id }}-campos" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5V6.75a3.375 3.375 0 00-3.375-3.375H6.75A2.25 2.25 0 004.5 5.625v12.75a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25v-4.125zM14.625 3.75V8.25h4.5" /></svg>
                        <span data-acta-ocr-label>Extraer datos con OCR</span>
                    </button>
                </div>
            @endif
            @if ($esActaNacimiento)
                @php($actaCampos = collect($captura->campos)->keyBy('clave'))
                @php($gruposActa = [
                    ['id' => 'acta-encabezado', 'titulo' => '1. Encabezado del acta', 'ayuda' => 'Oficialía y libro con los que se resguardó el acta.', 'ayuda-letra' => 'Los campos "letra" son opcionales: llénalos solo si el acta trae la cifra escrita además con letras.', 'filas' => [
                        ['lugar_registro'],
                        ['no_oficialia', 'no_oficialia_letra'],
                        ['no_libro', 'no_libro_letra'],
                    ]],
                    ['id' => 'acta-interesado', 'titulo' => '2. El acta y el interesado', 'ayuda' => 'Número del acta y datos del solicitante tal como aparecen en el cuerpo del acta.', 'filas' => [
                        ['numero_acta', 'no_acta_letra'],
                        ['lugar_nacimiento'],
                        ['dia_nacimiento', 'dia_nacimiento_letra'],
                        ['mes_nacimiento', 'ano_nacimiento', 'ano_nacimiento_letra'],
                        ['nombre_padre', 'nombre_madre'],
                    ]],
                    ['id' => 'acta-certificacion', 'titulo' => '3. Certificación', 'ayuda' => 'Quien certifica y la fecha de certificación al pie del acta.', 'filas' => [
                        ['nombre_certifica'],
                        ['cargo'],
                        ['dia_certificacion', 'dia_certificacion_letra'],
                        ['mes_certificacion', 'ano_certificacion', 'ano_certificacion_letra'],
                    ]],
                ])
                <div class="space-y-5" id="captura-{{ $captura->id }}-campos">
                    <p class="text-xs font-semibold text-neza-muted"><span class="text-rose-700">*</span> Obligatorio. Los campos sombreados son opcionales (solo si el acta los trae).</p>
                    @foreach ($gruposActa as $grupo)
                        <fieldset class="rounded-lg border border-neza-line p-4" aria-labelledby="{{ $grupo['id'] }}-titulo">
                            <legend id="{{ $grupo['id'] }}-titulo" class="px-1 text-sm font-bold text-neza-wine">{{ $grupo['titulo'] }}</legend>
                            <p class="mb-3 text-xs text-neza-muted">{{ $grupo['ayuda'] }}</p>
                            @if (isset($grupo['ayuda-letra']))<p class="mb-3 text-xs text-neza-muted">{{ $grupo['ayuda-letra'] }}</p>@endif
                            <div class="space-y-4">
                                @foreach ($grupo['filas'] as $fila)
                                    <div @class(['grid gap-4', 'sm:grid-cols-2' => count($fila) === 2, 'sm:grid-cols-3' => count($fila) === 3])>
                                        @foreach ($fila as $claveCampo)
                                            @php($campo = $actaCampos->get($claveCampo))
                                            @if ($campo && $campo->tipo !== 'DOMICILIO')
                                                <div>
                                                    <label for="captura-{{ $campo->id }}" class="block text-sm font-semibold text-neza-ink">{{ $campo->etiqueta }} @if ($campo->requerido)<span class="text-rose-700">*</span>@endif</label>
                                                    <input id="captura-{{ $campo->id }}" name="respuestas[{{ $campo->id }}]" value="{{ old('respuestas.'.$campo->id, is_string($campo->valor) ? $campo->valor : '') }}" type="text" maxlength="500" placeholder="{{ $campo->requerido ? '' : 'Opcional' }}" @required($campo->requerido) @class(['mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-neza-wine focus:border-neza-wine', 'bg-neza-canvas text-neza-muted' => ! $campo->requerido, 'bg-white' => $campo->requerido, 'border-rose-600' => $errors->has('respuestas.'.$campo->id)])>
                                                    @error('respuestas.'.$campo->id)<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>
            @else
                <div class="grid gap-5 sm:grid-cols-2">
                    @foreach ($captura->campos as $campo)
                        <div @class(['sm:col-span-2' => $campo->tipo === 'DOMICILIO'])>
                            <label for="captura-{{ $campo->id }}" class="block text-sm font-semibold text-neza-ink">{{ $campo->etiqueta }} @if ($campo->requerido)<span class="text-rose-700">*</span>@endif</label>
                            @if ($campo->tipo === 'DOMICILIO')
                                @php($domicilio = old('respuestas.'.$campo->id, is_array($campo->valor) ? $campo->valor : []))
                                <div class="mt-2" data-domicilio data-indice-url="{{ asset('data/capa.json') }}">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        @foreach (config('formulario.campos_domicilio') as $clave => $subcampo)
                                            <div @class(['sm:col-span-2' => $clave === 'calle'])>
                                                <label for="captura-{{ $campo->id }}-{{ $clave }}" class="block text-xs font-semibold text-neza-muted">{{ $subcampo['etiqueta'] }}</label>
                                                <input id="captura-{{ $campo->id }}-{{ $clave }}" data-domicilio-campo="{{ $clave }}" name="respuestas[{{ $campo->id }}][{{ $clave }}]" type="{{ $subcampo['tipo'] }}" value="{{ $domicilio[$clave] ?? ($subcampo['valor'] ?? '') }}" @if (isset($subcampo['inputmode'])) inputmode="{{ $subcampo['inputmode'] }}" @endif @if (isset($subcampo['pattern'])) pattern="{{ $subcampo['pattern'] }}" @endif @if (in_array($clave, ['colonia', 'calle', 'numero'], true)) list="captura-{{ $campo->id }}-{{ $clave }}-opciones" @endif @readonly(($subcampo['disabled'] ?? false) || $clave === 'codigo_postal') @required($campo->requerido && ! ($subcampo['disabled'] ?? false)) class="mt-1 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm read-only:bg-neza-canvas read-only:text-neza-muted">
                                            </div>
                                        @endforeach
                                    </div>
                                    <datalist id="captura-{{ $campo->id }}-colonia-opciones" data-domicilio-colonias></datalist><datalist id="captura-{{ $campo->id }}-calle-opciones" data-domicilio-calles></datalist><datalist id="captura-{{ $campo->id }}-numero-opciones" data-domicilio-numeros></datalist>
                                    <input type="hidden" name="respuestas[{{ $campo->id }}][latitud]" data-domicilio-latitud value="{{ $domicilio['latitud'] ?? '' }}"><input type="hidden" name="respuestas[{{ $campo->id }}][longitud]" data-domicilio-longitud value="{{ $domicilio['longitud'] ?? '' }}">
                                </div>
                            @else
                                <input id="captura-{{ $campo->id }}" name="respuestas[{{ $campo->id }}]" value="{{ old('respuestas.'.$campo->id, is_string($campo->valor) ? $campo->valor : '') }}" type="text" maxlength="500" @required($campo->requerido) class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error('respuestas.'.$campo->id) border-rose-600 @enderror">
                            @endif
                            @error('respuestas.'.$campo->id)<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
            @endif
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-neza-muted">Guarda solo después de verificar cada dato contra el acta.</p>
                <button type="submit" class="rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Guardar datos verificados</button>
            </div>
        </form>
    </section>
@endforeach
