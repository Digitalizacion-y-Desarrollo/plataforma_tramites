@foreach ($capturasAdministrativas as $captura)
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
            <div class="flex justify-end"><button type="submit" class="rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Guardar datos verificados</button></div>
        </form>
    </section>
@endforeach
