<dialog id="accessibility-dialog" aria-labelledby="accessibility-title" aria-describedby="accessibility-description" class="m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-2xl border border-neza-line bg-white p-0 text-neza-ink shadow-2xl backdrop:bg-neza-ink/70">
    <div class="p-5 sm:p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-neza-wine">Preferencias de visualización</p>
                <h2 id="accessibility-title" class="mt-1 text-2xl font-bold">Accesibilidad</h2>
            </div>
            <button type="button" data-accessibility-close aria-label="Cerrar opciones de accesibilidad" class="grid size-11 shrink-0 place-items-center rounded-xl text-neza-ink hover:bg-neza-wine-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <p id="accessibility-description" class="mt-2 text-sm leading-relaxed text-neza-muted">Personaliza cómo ves el portal. Los ajustes se conservan únicamente en este navegador.</p>

        <fieldset class="mt-6 border-t border-neza-line pt-5">
            <legend class="font-bold">Tamaño de texto y controles</legend>
            <p class="mt-1 text-sm text-neza-muted">Amplía la interfaz sin cambiar la configuración de tu dispositivo.</p>
            <div class="mt-3 grid grid-cols-3 gap-2">
                @foreach ([['normal', 'Normal'], ['large', 'Grande'], ['xlarge', 'Muy grande']] as [$valor, $etiqueta])
                    <label class="cursor-pointer">
                        <input type="radio" name="accessibility-text" value="{{ $valor }}" class="peer sr-only" @checked($valor === 'normal')>
                        <span class="flex min-h-12 items-center justify-center rounded-xl border border-neza-line px-2 text-center text-sm font-semibold text-neza-ink transition hover:border-neza-wine peer-checked:border-neza-wine peer-checked:bg-neza-wine-soft peer-checked:text-neza-wine peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-neza-wine">{{ $etiqueta }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <fieldset class="mt-6 border-t border-neza-line pt-5">
            <legend class="font-bold">Contraste</legend>
            <p class="mt-1 text-sm text-neza-muted">Distingue mejor texto, bordes y botones.</p>
            <div class="mt-3 grid grid-cols-2 gap-2">
                @foreach ([['normal', 'Normal'], ['high', 'Alto contraste']] as [$valor, $etiqueta])
                    <label class="cursor-pointer">
                        <input type="radio" name="accessibility-contrast" value="{{ $valor }}" class="peer sr-only" @checked($valor === 'normal')>
                        <span class="flex min-h-12 items-center justify-center rounded-xl border border-neza-line px-2 text-center text-sm font-semibold text-neza-ink transition hover:border-neza-wine peer-checked:border-neza-wine peer-checked:bg-neza-wine-soft peer-checked:text-neza-wine peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-neza-wine">{{ $etiqueta }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <fieldset class="mt-6 border-t border-neza-line pt-5">
            <legend class="font-bold">Movimiento</legend>
            <p class="mt-1 text-sm text-neza-muted">La opción «Sistema» respeta la preferencia de movimiento de tu dispositivo.</p>
            <div class="mt-3 grid grid-cols-2 gap-2">
                @foreach ([['system', 'Sistema'], ['reduced', 'Reducir movimiento']] as [$valor, $etiqueta])
                    <label class="cursor-pointer">
                        <input type="radio" name="accessibility-motion" value="{{ $valor }}" class="peer sr-only" @checked($valor === 'system')>
                        <span class="flex min-h-12 items-center justify-center rounded-xl border border-neza-line px-2 text-center text-sm font-semibold text-neza-ink transition hover:border-neza-wine peer-checked:border-neza-wine peer-checked:bg-neza-wine-soft peer-checked:text-neza-wine peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-neza-wine">{{ $etiqueta }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-neza-line pt-5 sm:flex-row sm:justify-between">
            <button type="button" data-accessibility-reset class="min-h-11 rounded-xl border border-neza-line px-4 text-sm font-bold text-neza-wine hover:bg-neza-wine-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Restablecer ajustes</button>
            <button type="button" data-accessibility-close class="min-h-11 rounded-xl bg-neza-wine px-5 text-sm font-bold text-white hover:bg-neza-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Listo</button>
        </div>
    </div>
</dialog>
