<div>
    <label for="{{ $modo }}-departamento" class="block text-sm font-semibold text-neza-ink">Departamento responsable <span class="text-rose-700">*</span></label>
    <select id="{{ $modo }}-departamento" name="departamento_accesos_id" required data-tramite-departamento class="mt-2 w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm text-neza-ink @error('departamento_accesos_id') border-rose-600 @enderror">
        <option value="">Selecciona un departamento</option>
        @foreach (collect($departamentos)->whereNull('parent_id') as $departamento)
            <option value="{{ $departamento['id'] }}" @selected(old('_modal') === $modo && old('departamento_accesos_id') === $departamento['id'])>{{ $departamento['nombre'] }}</option>
        @endforeach
    </select>
    @error('departamento_accesos_id')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
</div>
<div>
    <label for="{{ $modo }}-formulario" class="block text-sm font-semibold text-neza-ink">Formulario asociado <span class="font-normal text-neza-muted">(opcional)</span></label>
    <select id="{{ $modo }}-formulario" name="formulario_version_id" data-tramite-formulario class="mt-2 w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm text-neza-ink @error('formulario_version_id') border-rose-600 @enderror">
        <option value="">Sin formulario (trámite informativo o presencial)</option>
        @foreach ($formularios as $version)
            <option value="{{ $version->id }}" data-departamento="{{ $version->formulario->departamento_accesos_id }}" @selected(old('_modal') === $modo && old('formulario_version_id') === $version->id)>{{ $version->formulario->nombre }} · v{{ $version->numero_version }}</option>
        @endforeach
    </select>
    <p class="mt-2 text-xs text-neza-muted">Solo se muestran formularios del departamento elegido.</p>
    @error('formulario_version_id')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
</div>
