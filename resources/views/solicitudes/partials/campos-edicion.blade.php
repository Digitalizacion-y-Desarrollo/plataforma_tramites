@foreach ($campos as $campo)
    @php
        $clave = 'respuestas.'.$campo->id;
        $idBase = 'editar-campo-'.$campo->id;
        $valorActual = $valores[$campo->id] ?? null;
        $valorDomicilio = is_array($valorActual) ? $valorActual : [];
        $valorEscalar = is_scalar($valorActual) ? $valorActual : null;
        $claseControl = 'mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft';
        $claseError = 'border-rose-600';
    @endphp

    <div>
        @if (in_array($campo->tipo, ['RADIO', 'DOMICILIO', 'DOMICILIO_FUERA_NEZA', 'DOMICILIO_USA'], true))
            <p class="block text-sm font-semibold text-neza-ink">{{ $campo->etiqueta }} @if ($campo->requerido)<span class="text-rose-700">*</span>@endif</p>
        @else
            <label for="{{ $idBase }}" class="block text-sm font-semibold text-neza-ink">{{ $campo->etiqueta }} @if ($campo->requerido)<span class="text-rose-700">*</span>@endif</label>
        @endif

        @if ($campo->tipo === 'TEXTAREA')
            <textarea id="{{ $idBase }}" name="respuestas[{{ $campo->id }}]" rows="4" @required($campo->requerido) class="{{ $claseControl }} @error($clave) {{ $claseError }} @enderror">{{ old($clave, $valorEscalar) }}</textarea>
        @elseif ($campo->tipo === 'DOMICILIO')
            <div class="mt-2 space-y-3" data-domicilio-admin data-indice-url="{{ asset('data/capa.json') }}">
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (config('formulario.campos_domicilio') as $subclave => $subcampo)
                        <div @class(['sm:col-span-2' => $subclave === 'calle'])>
                            <label for="{{ $idBase }}-{{ $subclave }}" class="block text-xs font-semibold text-neza-muted">{{ $subcampo['etiqueta'] }}</label>
                            <input id="{{ $idBase }}-{{ $subclave }}" data-domicilio-campo="{{ $subclave }}" name="respuestas[{{ $campo->id }}][{{ $subclave }}]" type="{{ $subcampo['tipo'] }}" maxlength="{{ $subclave === 'codigo_postal' ? 5 : ($subclave === 'numero' ? 50 : 100) }}" value="{{ old($clave.'.'.$subclave, $valorDomicilio[$subclave] ?? ($subcampo['valor'] ?? '')) }}" @if (in_array($subclave, ['colonia', 'calle', 'numero'], true)) list="{{ $idBase }}-{{ $subclave }}-opciones" @endif @if ($subcampo['disabled'] ?? false) readonly @endif @required($campo->requerido) class="mt-1 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm read-only:bg-neza-canvas read-only:text-neza-muted @error($clave.'.'.$subclave) {{ $claseError }} @enderror">
                            @error($clave.'.'.$subclave)<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
                <datalist id="{{ $idBase }}-colonia-opciones" data-domicilio-colonias></datalist>
                <datalist id="{{ $idBase }}-calle-opciones" data-domicilio-calles></datalist>
                <datalist id="{{ $idBase }}-numero-opciones"></datalist>
                <input type="hidden" name="respuestas[{{ $campo->id }}][latitud]" data-domicilio-latitud value="{{ old($clave.'.latitud', $valorDomicilio['latitud'] ?? '') }}">
                <input type="hidden" name="respuestas[{{ $campo->id }}][longitud]" data-domicilio-longitud value="{{ old($clave.'.longitud', $valorDomicilio['longitud'] ?? '') }}">
                <p class="text-xs text-neza-muted">Al cambiar la colonia se actualiza el código postal y la lista de calles disponibles.</p>
            </div>
        @elseif ($campo->tipo === 'DOMICILIO_FUERA_NEZA')
            <div class="mt-2 grid gap-3 sm:grid-cols-2">
                @foreach (config('formulario.campos_domicilio_fuera_neza') as $subclave => $subcampo)
                    <div @class(['sm:col-span-2' => in_array($subclave, ['municipio', 'calle'], true)])>
                        <label for="{{ $idBase }}-{{ $subclave }}" class="block text-xs font-semibold text-neza-muted">{{ $subcampo['etiqueta'] }}</label>
                        <input id="{{ $idBase }}-{{ $subclave }}" name="respuestas[{{ $campo->id }}][{{ $subclave }}]" type="{{ $subcampo['tipo'] }}" maxlength="{{ $subcampo['maxlength'] }}" value="{{ old($clave.'.'.$subclave, $valorDomicilio[$subclave] ?? '') }}" @if ($subclave === 'codigo_postal') inputmode="numeric" pattern="[0-9]{5}" @endif @required($campo->requerido) class="mt-1 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error($clave.'.'.$subclave) {{ $claseError }} @enderror">
                        @error($clave.'.'.$subclave)<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        @elseif ($campo->tipo === 'DOMICILIO_USA')
            <div class="mt-2 grid gap-3 sm:grid-cols-2">
                @foreach (config('formulario.campos_domicilio_usa') as $subclave => $subcampo)
                    @php($requeridoSubcampo = $campo->requerido && ($subcampo['requerido'] ?? true))
                    <div @class(['sm:col-span-2' => $subclave === 'calle'])>
                        <label for="{{ $idBase }}-{{ $subclave }}" class="block text-xs font-semibold text-neza-muted">{{ $subcampo['etiqueta'] }}</label>
                        @if ($subclave === 'estado')
                            <select id="{{ $idBase }}-{{ $subclave }}" name="respuestas[{{ $campo->id }}][{{ $subclave }}]" @required($requeridoSubcampo) class="mt-1 w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm @error($clave.'.'.$subclave) {{ $claseError }} @enderror">
                                <option value="">Selecciona un estado</option>
                                @foreach (config('formulario.estados_usa') as $codigo => $nombre)
                                    <option value="{{ $codigo }}" @selected((string) old($clave.'.estado', $valorDomicilio['estado'] ?? '') === (string) $codigo)>{{ $nombre }}</option>
                                @endforeach
                            </select>
                        @else
                            <input id="{{ $idBase }}-{{ $subclave }}" name="respuestas[{{ $campo->id }}][{{ $subclave }}]" type="{{ $subcampo['tipo'] }}" maxlength="{{ $subcampo['maxlength'] }}" value="{{ old($clave.'.'.$subclave, $valorDomicilio[$subclave] ?? '') }}" @if ($subclave === 'codigo_postal') pattern="[0-9]{5}(-[0-9]{4})?" @endif @required($requeridoSubcampo) class="mt-1 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error($clave.'.'.$subclave) {{ $claseError }} @enderror">
                        @endif
                        @error($clave.'.'.$subclave)<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        @elseif ($campo->tipo === 'ESTADO')
            <select id="{{ $idBase }}" name="respuestas[{{ $campo->id }}]" @required($campo->requerido) class="{{ $claseControl }} bg-white @error($clave) {{ $claseError }} @enderror">
                <option value="">Selecciona una entidad</option>
                @foreach (config('formulario.entidades_federativas') as $claveEstado => $entidad)
                    <option value="{{ $claveEstado }}" @selected((string) old($clave, $valorEscalar) === (string) $claveEstado)>{{ $entidad }}</option>
                @endforeach
            </select>
        @elseif (in_array($campo->tipo, ['SELECT', 'MULTISELECT'], true))
            <select id="{{ $idBase }}" name="respuestas[{{ $campo->id }}]{{ $campo->tipo === 'MULTISELECT' ? '[]' : '' }}" @if ($campo->tipo === 'MULTISELECT') multiple @endif @required($campo->requerido) class="{{ $claseControl }} bg-white @error($clave) {{ $claseError }} @enderror">
                @if ($campo->tipo !== 'MULTISELECT')
                    <option value="">Selecciona una opción</option>
                @endif
                @foreach ($campo->opciones as $opcion)
                    <option value="{{ $opcion->valor }}" @selected($campo->tipo === 'MULTISELECT' ? in_array($opcion->valor, (array) old($clave, (array) $valorActual), true) : (string) old($clave, $valorEscalar) === (string) $opcion->valor)>{{ $opcion->etiqueta }}</option>
                @endforeach
            </select>
        @elseif ($campo->tipo === 'RADIO')
            <div class="mt-2 space-y-2">
                @foreach ($campo->opciones as $opcion)
                    <label class="flex items-center gap-2 text-sm text-neza-ink">
                        <input id="{{ $loop->first ? $idBase : $idBase.'-'.$opcion->id }}" type="radio" name="respuestas[{{ $campo->id }}]" value="{{ $opcion->valor }}" @checked((string) old($clave, $valorEscalar) === (string) $opcion->valor) @required($campo->requerido && $loop->first) class="size-4 border-neza-line text-neza-wine focus:ring-neza-wine">
                        {{ $opcion->etiqueta }}
                    </label>
                @endforeach
            </div>
        @elseif ($campo->tipo === 'CHECKBOX')
            <label class="mt-2 flex items-center gap-2 text-sm text-neza-ink">
                <input id="{{ $idBase }}" type="checkbox" name="respuestas[{{ $campo->id }}]" value="1" @checked((string) old($clave, $valorEscalar) === '1') @required($campo->requerido) class="size-4 rounded border-neza-line text-neza-wine focus:ring-neza-wine">
                Seleccionar
            </label>
        @else
            <input id="{{ $idBase }}" name="respuestas[{{ $campo->id }}]" type="{{ $campo->tipo === 'EMAIL' ? 'email' : ($campo->tipo === 'DATE' ? 'date' : ($campo->tipo === 'NUMBER' ? 'number' : 'text')) }}" @if ($campo->tipo === 'NUMBER') step="any" @endif @if ($campo->tipo === 'CURP') maxlength="18" @endif value="{{ old($clave, $valorEscalar) }}" @required($campo->requerido) @class([$claseControl, 'uppercase' => in_array($campo->tipo, ['CURP', 'RFC'], true), $claseError => $errors->has($clave)])>
        @endif

        @error($clave)<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
    </div>
@endforeach
