@php
    $valorAdministrativo = fn (string $campo) => old('_modal') === $modo ? old($campo) : ($tramite?->{$campo} ?? null);
@endphp
<details class="rounded-lg border border-neza-line p-4" open>
    <summary class="cursor-pointer text-sm font-semibold text-neza-ink">Información del documento</summary>
    <div class="mt-4 grid gap-5">
        @foreach (['doc_a_obtener' => 'Documento a obtener', 'vigencia_doc' => 'Vigencia del documento', 'sujeto_inspeccion' => 'Sujeto de inspección', 'criterios_resolucion' => 'Criterios de resolución', 'unidad_administrativa_responsable' => 'Unidad administrativa responsable', 'fund_juridico' => 'Fundamento jurídico'] as $campo => $etiqueta)
            <div><label for="{{ $modo }}-{{ $campo }}" class="block text-sm font-semibold text-neza-ink">{{ $etiqueta }}</label><textarea id="{{ $modo }}-{{ $campo }}" name="{{ $campo }}" rows="3" maxlength="5000" class="mt-2 w-full resize-y rounded-lg border border-neza-line px-3 py-2.5 text-sm text-neza-ink @error($campo) border-rose-600 @enderror">{{ $valorAdministrativo($campo) }}</textarea>@if (old('_modal') === $modo) @error($campo)<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror @endif</div>
        @endforeach
    </div>
</details>
