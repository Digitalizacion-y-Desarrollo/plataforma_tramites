@extends('layouts.app')

@section('title', 'Configuración general')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <p class="text-sm font-semibold text-neza-wine">Configuración</p>
            <h1 class="mt-1 text-3xl font-bold text-neza-ink">Configuración general</h1>
            <p class="mt-2 text-sm text-neza-muted">Define cómo se emitirá la ficha de pago de los trámites que requieren pago.</p>
        </div>

        <form action="{{ route('configuracion-general.update') }}" method="POST" class="rounded-2xl border border-neza-line bg-white p-6 shadow-sm" data-confirm-action data-confirm-title="¿Guardar configuración?" data-confirm-text="El nuevo flujo se aplicará a las fichas de pago que se emitan a partir de ahora." data-confirm-button="Sí, guardar">
            @csrf
            @method('PUT')
            <fieldset>
                <legend class="text-base font-bold text-neza-ink">Origen de la ficha de pago</legend>
                <p class="mt-1 text-sm text-neza-muted">Las fichas ya enviadas conservan su historial y no se modifican.</p>
                <div class="mt-5 space-y-3">
                    <label class="flex cursor-pointer gap-4 rounded-xl border border-neza-line p-4 transition hover:border-neza-wine">
                        <input type="radio" name="origen_ficha_pago" value="SISTEMA_EXTERNO" @checked(old('origen_ficha_pago', $configuracion->origen_ficha_pago) === 'SISTEMA_EXTERNO') class="mt-1 size-4 border-neza-line text-neza-wine focus:ring-neza-wine">
                        <span><span class="block text-sm font-bold text-neza-ink">Ficha desde sistema externo</span><span class="mt-1 block text-sm text-neza-muted">El servidor público carga el PDF oficial, agrega indicaciones y lo envía a la ciudadanía.</span></span>
                    </label>
                    <label class="flex cursor-pointer gap-4 rounded-xl border border-neza-line p-4 transition hover:border-neza-wine">
                        <input type="radio" name="origen_ficha_pago" value="GENERADA_EN_PORTAL" @checked(old('origen_ficha_pago', $configuracion->origen_ficha_pago) === 'GENERADA_EN_PORTAL') class="mt-1 size-4 border-neza-line text-neza-wine focus:ring-neza-wine">
                        <span><span class="block text-sm font-bold text-neza-ink">Ficha generada en el portal</span><span class="mt-1 block text-sm text-neza-muted">El portal crea el PDF con importe, vigencia y referencia antes de enviarlo por correo.</span></span>
                    </label>
                </div>
                @error('origen_ficha_pago')<p class="mt-3 text-sm text-rose-700">{{ $message }}</p>@enderror
            </fieldset>
            <div class="mt-6 flex justify-end"><button type="submit" class="rounded-lg bg-neza-wine px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">Guardar configuración</button></div>
        </form>
    </div>
@endsection
