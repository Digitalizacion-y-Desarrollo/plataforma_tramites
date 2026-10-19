@extends('layouts.app')

@section('content')
    @php
        $preguntas = old('preguntas', $canalesAyuda->preguntas_frecuentes ?? []);
        $redes = old('redes', $canalesAyuda->redes_sociales ?? []);
        $redesPermitidas = [
            'Facebook' => 'images/social/facebook.svg',
            'Instagram' => 'images/social/instagram.svg',
            'TikTok' => 'images/social/tiktok.svg',
            'X' => 'images/social/x.svg',
            'Threads' => 'images/social/threads.svg',
        ];
    @endphp

    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <p class="text-sm font-semibold text-neza-wine">Configuración</p>
            <h1 class="mt-1 text-3xl font-bold text-neza-ink">Canales de ayuda</h1>
            <p class="mt-2 text-sm text-neza-muted">Esta información se mostrará en la sección de orientación y soporte y en el pie de página público.</p>
        </div>

        <form action="{{ route('canales-ayuda.update') }}" method="POST" class="space-y-6" data-canales-ayuda-form>
            @csrf
            @method('PUT')

            <section class="rounded-xl border border-neza-line bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-neza-ink">Preguntas frecuentes</h2>
                        <p class="mt-1 text-sm text-neza-muted">Agrega las dudas y respuestas que se publicarán en el acordeón de ayuda.</p>
                    </div>
                    <button type="button" data-agregar-pregunta class="rounded-lg border border-neza-wine px-3 py-2 text-sm font-semibold text-neza-wine hover:bg-neza-wine-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Agregar pregunta</button>
                </div>
                <div class="mt-5 space-y-4" data-preguntas>
                    @foreach ($preguntas as $indice => $pregunta)
                        <details class="group rounded-xl border border-neza-line" data-pregunta @if ($errors->has('preguntas.'.$indice.'.pregunta') || $errors->has('preguntas.'.$indice.'.respuesta')) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 text-sm font-semibold text-neza-ink [&::-webkit-details-marker]:hidden">
                                <span data-pregunta-resumen>{{ $pregunta['pregunta'] ?: 'Pregunta sin título' }}</span>
                                <svg class="size-4 shrink-0 text-neza-wine transition group-open:rotate-180" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </summary>
                            <div class="border-t border-neza-line p-4">
                            <div class="flex justify-end"><button type="button" data-eliminar-fila class="text-xs font-semibold text-neza-wine hover:underline">Eliminar</button></div>
                            <div class="mt-2">
                                <label class="mb-1.5 block text-sm font-semibold text-neza-ink" for="pregunta-{{ $indice }}">Pregunta</label>
                                <input id="pregunta-{{ $indice }}" name="preguntas[{{ $indice }}][pregunta]" type="text" maxlength="500" value="{{ $pregunta['pregunta'] ?? '' }}" data-pregunta-titulo class="w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                                @error('preguntas.'.$indice.'.pregunta') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
                            </div>
                            <div class="mt-4">
                                <label class="mb-1.5 block text-sm font-semibold text-neza-ink" for="respuesta-{{ $indice }}">Respuesta</label>
                                <textarea id="respuesta-{{ $indice }}" name="preguntas[{{ $indice }}][respuesta]" rows="4" maxlength="2000" class="w-full resize-y rounded-lg border border-neza-line px-3 py-2.5 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">{{ $pregunta['respuesta'] ?? '' }}</textarea>
                                @error('preguntas.'.$indice.'.respuesta') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
                            </div>
                            </div>
                        </details>
                    @endforeach
                </div>
                @error('preguntas') <p class="mt-2 text-xs text-rose-700">{{ $message }}</p> @enderror
            </section>

            <section class="rounded-xl border border-neza-line bg-white p-5 shadow-sm sm:p-7">
                <h2 class="text-lg font-bold text-neza-ink">Datos de contacto</h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="telefono_contacto" class="mb-1.5 block text-sm font-semibold text-neza-ink">Teléfono de contacto</label>
                        <input id="telefono_contacto" name="telefono_contacto" type="text" maxlength="50" value="{{ old('telefono_contacto', $canalesAyuda->telefono_contacto) }}" placeholder="55 5716 9070" class="w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                        @error('telefono_contacto') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="email_contacto" class="mb-1.5 block text-sm font-semibold text-neza-ink">Correo de contacto</label>
                        <input id="email_contacto" name="email_contacto" type="email" maxlength="191" value="{{ old('email_contacto', $canalesAyuda->email_contacto) }}" placeholder="atencion@nezahualcoyotl.gob.mx" class="w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                        @error('email_contacto') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mt-5">
                    <label for="horario_atencion" class="mb-1.5 block text-sm font-semibold text-neza-ink">Horario de atención</label>
                    <textarea id="horario_atencion" name="horario_atencion" rows="3" maxlength="500" placeholder="Lunes a viernes, de 9:00 a 18:00 h." class="w-full resize-y rounded-lg border border-neza-line px-3 py-2.5 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">{{ old('horario_atencion', $canalesAyuda->horario_atencion) }}</textarea>
                    @error('horario_atencion') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
                </div>
            </section>

            <section class="rounded-xl border border-neza-line bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div><h2 class="text-lg font-bold text-neza-ink">Redes sociales</h2><p class="mt-1 text-sm text-neza-muted">Selecciona una red social oficial y captura la URL completa del perfil.</p></div>
                    <button type="button" data-agregar-red class="rounded-lg border border-neza-wine px-3 py-2 text-sm font-semibold text-neza-wine hover:bg-neza-wine-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Agregar red</button>
                </div>
                <div class="mt-5 space-y-3" data-redes>
                    @foreach ($redes as $indice => $red)
                        <div class="grid gap-3 rounded-xl border border-neza-line p-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)_auto] sm:items-end" data-red>
                            <div><label class="mb-1.5 block text-sm font-semibold text-neza-ink" for="red-nombre-{{ $indice }}">Red social</label><div class="flex items-center gap-2"><img data-red-logo src="{{ isset($redesPermitidas[$red['nombre'] ?? '']) ? asset($redesPermitidas[$red['nombre']]) : '' }}" alt="" class="size-7 shrink-0 object-contain {{ isset($redesPermitidas[$red['nombre'] ?? '']) ? '' : 'hidden' }}"><select id="red-nombre-{{ $indice }}" name="redes[{{ $indice }}][nombre]" data-red-select class="w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm"><option value="">Selecciona una red</option>@foreach ($redesPermitidas as $nombre => $logo)<option value="{{ $nombre }}" @selected(($red['nombre'] ?? '') === $nombre)>{{ $nombre }}</option>@endforeach</select></div>@error('redes.'.$indice.'.nombre') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror</div>
                            <div><label class="mb-1.5 block text-sm font-semibold text-neza-ink" for="red-url-{{ $indice }}">URL</label><input id="red-url-{{ $indice }}" name="redes[{{ $indice }}][url]" type="url" maxlength="2048" value="{{ $red['url'] ?? '' }}" placeholder="https://..." class="w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm">@error('redes.'.$indice.'.url') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror</div>
                            <button type="button" data-eliminar-fila class="rounded-lg px-3 py-2.5 text-sm font-semibold text-neza-wine hover:bg-neza-wine-soft">Eliminar</button>
                        </div>
                    @endforeach
                </div>
                @error('redes') <p class="mt-2 text-xs text-rose-700">{{ $message }}</p> @enderror
            </section>

            <div class="flex justify-end"><button type="submit" class="rounded-lg bg-neza-wine px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine" data-guardar>Guardar cambios</button></div>
        </form>
    </div>

    <template data-pregunta-template><details open class="group rounded-xl border border-neza-line" data-pregunta><summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 text-sm font-semibold text-neza-ink [&::-webkit-details-marker]:hidden"><span data-pregunta-resumen>Pregunta sin título</span><svg class="size-4 shrink-0 text-neza-wine transition group-open:rotate-180" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg></summary><div class="border-t border-neza-line p-4"><div class="flex justify-end"><button type="button" data-eliminar-fila class="text-xs font-semibold text-neza-wine hover:underline">Eliminar</button></div><div class="mt-2"><label class="mb-1.5 block text-sm font-semibold text-neza-ink">Pregunta</label><input type="text" maxlength="500" data-pregunta-titulo class="w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm"></div><div class="mt-4"><label class="mb-1.5 block text-sm font-semibold text-neza-ink">Respuesta</label><textarea rows="4" maxlength="2000" class="w-full resize-y rounded-lg border border-neza-line px-3 py-2.5 text-sm"></textarea></div></div></details></template>
    <template data-red-template><div class="grid gap-3 rounded-xl border border-neza-line p-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)_auto] sm:items-end" data-red><div><label class="mb-1.5 block text-sm font-semibold text-neza-ink">Red social</label><div class="flex items-center gap-2"><img data-red-logo alt="" class="hidden size-7 shrink-0 object-contain"><select data-red-select class="w-full rounded-lg border border-neza-line bg-white px-3 py-2.5 text-sm"><option value="">Selecciona una red</option>@foreach ($redesPermitidas as $nombre => $logo)<option value="{{ $nombre }}">{{ $nombre }}</option>@endforeach</select></div></div><div><label class="mb-1.5 block text-sm font-semibold text-neza-ink">URL</label><input type="url" maxlength="2048" placeholder="https://..." class="w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm"></div><button type="button" data-eliminar-fila class="rounded-lg px-3 py-2.5 text-sm font-semibold text-neza-wine hover:bg-neza-wine-soft">Eliminar</button></div></template>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-canales-ayuda-form]');
            const agregarFila = (contenedor, plantilla, tipo) => {
                const indice = Date.now();
                const fragmento = plantilla.content.cloneNode(true);
                const campos = fragmento.querySelectorAll('input, textarea, select');
                campos[0].name = `${tipo}[${indice}][${tipo === 'preguntas' ? 'pregunta' : 'nombre'}]`;
                campos[1].name = `${tipo}[${indice}][${tipo === 'preguntas' ? 'respuesta' : 'url'}]`;
                contenedor.appendChild(fragmento);
            };

            document.querySelector('[data-agregar-pregunta]').addEventListener('click', () => agregarFila(document.querySelector('[data-preguntas]'), document.querySelector('[data-pregunta-template]'), 'preguntas'));
            const logosRedes = @json(collect($redesPermitidas)->map(fn ($logo) => asset($logo)));
            document.querySelector('[data-agregar-red]').addEventListener('click', () => agregarFila(document.querySelector('[data-redes]'), document.querySelector('[data-red-template]'), 'redes'));
            form.addEventListener('click', (event) => { if (event.target.matches('[data-eliminar-fila]')) event.target.closest('[data-pregunta], [data-red]').remove(); });
            form.addEventListener('input', (event) => {
                if (!event.target.matches('[data-pregunta-titulo]')) return;
                event.target.closest('[data-pregunta]').querySelector('[data-pregunta-resumen]').textContent = event.target.value || 'Pregunta sin título';
            });
            form.addEventListener('change', (event) => {
                if (!event.target.matches('[data-red-select]')) return;
                const logo = event.target.closest('[data-red]').querySelector('[data-red-logo]');
                logo.src = logosRedes[event.target.value] || '';
                logo.classList.toggle('hidden', !event.target.value);
            });
            form.addEventListener('submit', () => { const boton = form.querySelector('[data-guardar]'); boton.disabled = true; boton.textContent = 'Guardando...'; });
        });
    </script>
@endsection
