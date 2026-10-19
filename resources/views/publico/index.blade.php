@extends('layouts.publico')

@section('title', 'Inicio')

@section('content')
    <section id="inicio" class="relative isolate min-h-[430px] overflow-hidden bg-[#340c1b]">
        <img src="{{ asset('images/neza-hero.jpg') }}" alt="Vista panorámica del Municipio de Nezahualcóyotl"
            class="absolute inset-0 -z-20 size-full object-cover">
        <div class="absolute inset-0 -z-10 bg-[#340c1b]/45 mix-blend-multiply"></div>
        <div class="mx-auto flex min-h-[430px] max-w-[1440px] items-center justify-center px-4 py-12 sm:px-6 lg:px-[72px]">
            <div
                class="w-full max-w-[980px] rounded-3xl border border-white/50 bg-white/95 p-5 text-center shadow-[0_18px_40px_rgba(52,12,27,0.16)] backdrop-blur-md sm:p-7">
                <p class="text-xs font-bold uppercase tracking-wide text-[#76531f]">Encuentra tu trámite</p>
                <h1 class="mt-2 text-3xl font-bold leading-tight text-neza-ink sm:text-[40px]">¿Qué necesitas hacer hoy?</h1>
                <p class="mx-auto mt-2 max-w-3xl text-sm leading-relaxed text-neza-muted sm:text-[15px]">Busca por palabra
                    clave y explora los trámites disponibles en el catálogo municipal.</p>

                <form method="GET" action="{{ route('portal.tramites.index') }}" class="mt-5 flex flex-col gap-3 sm:flex-row"
                    role="search">
                    <label for="buscar-tramite" class="sr-only">Buscar trámite, servicio o dependencia</label>
                    <div
                        class="flex h-16 flex-1 items-center gap-3 rounded-2xl border border-neza-line bg-white px-[18px] text-left focus-within:border-neza-wine focus-within:ring-2 focus-within:ring-neza-wine/15">
                        <svg class="size-5 shrink-0 text-neza-muted" aria-hidden="true" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.7" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m21 21-4.35-4.35m1.35-5.4a6.75 6.75 0 11-13.5 0 6.75 6.75 0 0113.5 0z" />
                        </svg>
                        <input id="buscar-tramite" name="q" value="{{ request('q') }}" type="search"
                            placeholder="Buscar trámite, servicio o dependencia"
                            class="min-w-0 flex-1 border-0 bg-transparent text-[15px] text-neza-ink outline-none placeholder:text-neza-muted">
                    </div>
                    <button type="submit"
                        class="h-16 rounded-2xl bg-neza-wine px-8 text-[15px] font-bold text-white transition hover:bg-[#4c0f26] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Buscar</button>
                </form>

                <a href="#consulta" class="mt-5 inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-neza-wine px-5 py-2.5 text-sm font-bold text-neza-wine transition hover:bg-neza-wine-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                    ¿Ya realizaste un trámite? Consulta su estado
                    <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
            </div>
        </div>
    </section>

    <section id="tramites" class="bg-[#f8f9fa] px-4 py-14 sm:px-6 lg:px-[72px] lg:py-16">
        <div class="mx-auto max-w-[1296px]">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-[#76531f]">Más consultados</p>
                    <h2 class="mt-1 text-2xl font-bold text-neza-ink sm:text-[30px]">Trámites destacados</h2>
                    <p class="mt-2 text-sm text-neza-muted">Inicia solicitudes, revisa requisitos y consulta la modalidad de
                        atención.</p>
                </div>
                <a href="{{ route('portal.tramites.index') }}"
                    class="inline-flex items-center gap-1.5 text-[13px] font-bold text-neza-wine hover:text-[#340c1b]">Ver
                    todos los trámites
                    <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </div>

            @php
                $tramitesDestacados = $tramites->take(4)->map(fn ($tramite) => [
                    $tramite->nombre,
                    $tramite->descripcion ?: 'Consulta la información de este trámite.',
                    collect($tramite->modalidades ?? [])->unique()->values()->count() > 1 ? 'Mixto' : (collect($tramite->modalidades ?? [])->first() === 'EN_LINEA' ? 'En línea' : 'Presencial'),
                    $tramite->tiempo_estimado ?: 'Tiempo por definir',
                    route('portal.tramites.detalle', $tramite),
                ]);
            @endphp
            <div class="mt-8 grid gap-4 md:grid-cols-2">
                @forelse ($tramitesDestacados as [$nombre, $descripcion, $modalidad, $tiempo, $url])
                    <article
                        class="group rounded-2xl border border-neza-line bg-white p-5 shadow-[0_8px_24px_rgba(31,41,55,0.05)] transition hover:-translate-y-0.5 hover:border-neza-wine/30 hover:shadow-[0_12px_30px_rgba(52,12,27,0.09)]">
                        <div class="flex items-start justify-between gap-4">
                            <div
                                class="grid size-10 shrink-0 place-items-center rounded-xl bg-neza-wine-soft text-neza-wine">
                                <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.6"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5V6.75a3.375 3.375 0 00-3.375-3.375H6.75A2.25 2.25 0 004.5 5.625v12.75a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25v-4.125z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.625 3.75V8.25h4.5" />
                                </svg>
                            </div>
                            <span
                                class="rounded-full bg-[#edf8f1] px-2.5 py-1 text-[10px] font-bold text-[#287a47]">{{ $modalidad }}</span>
                        </div>
                        <h3 class="mt-4 text-base font-bold text-neza-ink">{{ $nombre }}</h3>
                        <p class="mt-1.5 text-[13px] leading-relaxed text-neza-muted">{{ $descripcion }}</p>
                        <div class="mt-5 flex items-center justify-between border-t border-neza-line pt-4 text-xs">
                            <span class="flex items-center gap-1.5 text-neza-muted">
                                <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.7"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>{{ $tiempo }}
                            </span>
                            <a href="{{ $url }}"
                                class="inline-flex items-center gap-1 font-bold text-neza-wine">Ver trámite
                                <svg class="size-3.5 transition group-hover:translate-x-0.5" aria-hidden="true"
                                    fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        </div>
                    </article>
                @empty
                    <p class="col-span-full rounded-xl border border-dashed border-neza-line bg-white p-6 text-sm text-neza-muted">No hay trámites disponibles por el momento.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section id="consulta" class="bg-[#340c1b] px-4 py-12 text-white sm:px-6 lg:px-[72px] lg:py-14">
        <div class="mx-auto grid max-w-[1296px] items-center gap-10 lg:grid-cols-[0.85fr_1.15fr]">
            <div>
                <div class="grid size-11 place-items-center rounded-xl border border-white/15 bg-white/10 text-[#be985c]">
                    <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.7"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5V6.75a3.375 3.375 0 00-3.375-3.375H6.75A2.25 2.25 0 004.5 5.625v12.75a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25v-4.125z" />
                    </svg>
                </div>
                <p class="mt-5 text-[11px] font-bold uppercase tracking-wide text-[#be985c]">Seguimiento ciudadano</p>
                <h2 class="mt-2 max-w-md text-3xl font-bold leading-tight">Consulta el estado de tu trámite</h2>
                <p class="mt-3 max-w-lg text-sm leading-relaxed text-white/70">Ingresa tu CURP y el correo que usaste en tu solicitud. Te enviaremos un código temporal para acceder a tus trámites.</p>
            </div>
            <div class="rounded-2xl bg-white p-5 text-neza-ink shadow-2xl sm:p-7">
                <p class="text-xs font-bold uppercase tracking-wide text-neza-wine">Paso {{ $consultaPendiente ? '2' : '1' }} de 2</p>
                @if (session('consulta_aviso'))
                    <p role="status" class="mt-4 rounded-xl bg-neza-wine-soft p-3 text-sm text-neza-wine">{{ session('consulta_aviso') }}</p>
                @endif
                @if ($consultaPendiente)
                    <h3 class="mt-2 text-xl font-bold">Ingresa el código de verificación</h3>
                    <p class="mt-2 text-sm leading-relaxed text-neza-muted">Si tus datos coinciden con una solicitud, enviamos un código de seis dígitos a <strong class="text-neza-ink">{{ \Illuminate\Support\Str::mask($consultaPendiente['email'], '*', 2, max(0, strpos($consultaPendiente['email'], '@') - 2)) }}</strong>. Vence en 15 minutos.</p>
                    <form action="{{ route('consulta.token.verificar') }}" method="POST" class="mt-5">
                        @csrf
                        <label for="token-consulta" class="block text-sm font-bold">Código de verificación</label>
                        <input id="token-consulta" name="token" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required aria-describedby="token-ayuda @error('token') token-error @enderror" class="mt-2 h-12 w-full rounded-xl border border-neza-line px-4 text-sm outline-none focus:border-neza-wine focus:ring-2 focus:ring-neza-wine/15 @error('token') border-rose-600 @enderror">
                        @error('token')<p id="token-error" class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                        <p id="token-ayuda" class="mt-2 text-xs text-neza-muted">El código es de un solo uso. No lo compartas.</p>
                        <button type="submit" class="mt-5 min-h-11 w-full rounded-xl bg-neza-wine px-5 py-2.5 text-sm font-bold text-white hover:bg-neza-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine sm:w-auto">Entrar a mi portal</button>
                    </form>
                    <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-3 border-t border-neza-line pt-4 text-sm">
                        <form action="{{ route('consulta.token.solicitar') }}" method="POST">
                            @csrf
                            <input type="hidden" name="curp" value="{{ $consultaPendiente['curp'] }}">
                            <input type="hidden" name="email_contacto" value="{{ $consultaPendiente['email'] }}">
                            <button type="submit" data-reenviar-en="{{ $consultaPendiente['reenviar_en'] }}" class="font-bold text-neza-wine underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Reenviar código</button>
                        </form>
                        <form action="{{ route('consulta.cancelar') }}" method="POST">
                            @csrf
                            <button type="submit" class="font-bold text-neza-muted underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">Cambiar CURP o correo</button>
                        </form>
                    </div>
                    <p class="mt-4 text-xs text-neza-muted">¿No llegó? Revisa spam o vuelve a solicitarlo después de un minuto.</p>
                @else
                    <h3 class="mt-2 text-xl font-bold">Confirma tus datos</h3>
                    <p class="mt-2 text-sm text-neza-muted">Usa la CURP y el correo que proporcionaste al enviar tu solicitud.</p>
                    <form action="{{ route('consulta.token.solicitar') }}" method="POST" class="mt-5 space-y-4">
                        @csrf
                        <div>
                            <label for="curp-consulta" class="text-sm font-bold">CURP</label>
                            <input id="curp-consulta" name="curp" type="text" maxlength="18" autocomplete="off" required value="{{ old('curp') }}" placeholder="Ingresa tu CURP" class="mt-2 h-12 w-full rounded-xl border border-neza-line px-4 text-sm uppercase outline-none focus:border-neza-wine focus:ring-2 focus:ring-neza-wine/15 @error('curp') border-rose-600 @enderror">
                            @error('curp')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="correo-consulta" class="text-sm font-bold">Correo electrónico</label>
                            <input id="correo-consulta" name="email_contacto" type="email" autocomplete="email" required value="{{ old('email_contacto') }}" placeholder="correo@ejemplo.com" class="mt-2 h-12 w-full rounded-xl border border-neza-line px-4 text-sm outline-none focus:border-neza-wine focus:ring-2 focus:ring-neza-wine/15 @error('email_contacto') border-rose-600 @enderror">
                            @error('email_contacto')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="min-h-11 w-full rounded-xl bg-neza-wine px-5 py-2.5 text-sm font-bold text-white hover:bg-neza-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine sm:w-auto">Enviar código de acceso</button>
                    </form>
                    <p class="mt-4 border-t border-neza-line pt-4 text-xs text-neza-muted">Por tu seguridad, el acceso dura 30 minutos y solo muestra las solicitudes vinculadas a ambos datos.</p>
                @endif
            </div>
        </div>
    </section>

    <section id="servicios" class="bg-white px-4 py-14 sm:px-6 lg:px-[72px] lg:py-16">
        <div class="mx-auto max-w-[1296px]">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-[#76531f]">Más del municipio</p>
                    <h2 class="mt-2 text-2xl font-bold sm:text-[30px]">Servicios y enlaces de interés</h2>
                    <p class="mt-2 text-sm text-neza-muted">Accesos rápidos a información, pagos y servicios municipales.
                    </p>
                </div>
            </div>
            <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @forelse ($servicios as $servicio)
                    <a href="{{ $servicio->enlace }}" target="_blank" rel="noopener noreferrer"
                        class="group flex min-h-36 flex-col gap-3 rounded-xl border border-neza-line bg-neza-canvas p-4 transition hover:border-neza-wine/40 hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                        <img src="{{ asset('storage/'.$servicio->logo_storage_key) }}" alt="" class="size-10 rounded-lg object-contain" loading="lazy">
                        <span class="text-sm font-bold text-neza-ink">{{ $servicio->titulo }}</span>
                        <span class="line-clamp-3 text-xs leading-relaxed text-neza-muted">{{ $servicio->descripcion }}</span>
                        <span class="sr-only">Abre en una pestaña nueva</span>
                    </a>
                @empty
                    <p class="col-span-full rounded-xl border border-dashed border-neza-line p-6 text-sm text-neza-muted">No hay servicios ni enlaces de interés configurados por el momento.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section id="ayuda" class="bg-[#f8f9fa] px-4 py-14 sm:px-6 lg:px-[72px] lg:py-16">
        <div class="mx-auto grid max-w-[1296px] gap-8 lg:grid-cols-[1.25fr_0.75fr]">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wide text-[#76531f]">Orientación y soporte</p>
                <h2 class="mt-2 text-2xl font-bold sm:text-[30px]">¿Necesitas ayuda?</h2>
                <p class="mt-2 text-sm text-neza-muted">Resuelve tus dudas frecuentes o comunícate por los canales
                    oficiales.</p>
                <div class="mt-7 space-y-2">
                    @forelse ($canalesAyuda?->preguntas_frecuentes ?? [] as $pregunta)
                        <details class="group rounded-xl border border-neza-line bg-white px-4 py-3">
                            <summary
                                class="flex cursor-pointer list-none items-center justify-between gap-4 text-[13px] font-bold [&::-webkit-details-marker]:hidden">
                                {{ $pregunta['pregunta'] }}<svg
                                    class="size-4 shrink-0 text-neza-wine transition group-open:rotate-45"
                                    aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                                </svg></summary>
                            <p class="mt-3 border-t border-neza-line pt-3 text-[13px] leading-relaxed text-neza-muted">
                                {{ $pregunta['respuesta'] }}</p>
                        </details>
                    @empty
                        <p class="rounded-xl border border-dashed border-neza-line bg-white p-4 text-sm text-neza-muted">No hay preguntas frecuentes publicadas por el momento. Si tienes dudas, comunícate por los canales de atención.</p>
                    @endforelse
                </div>
            </div>
            <aside class="rounded-2xl border border-neza-line bg-white p-6 shadow-[0_10px_30px_rgba(31,41,55,0.06)]">
                <div class="flex items-start gap-3"><span
                        class="grid size-10 shrink-0 place-items-center rounded-xl bg-neza-wine-soft text-neza-wine"><svg
                            class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.7"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.142-4.03 7.5-9 7.5a10.3 10.3 0 01-3.555-.62L3 21l1.73-4.61A6.808 6.808 0 013 12c0-4.142 4.03-7.5 9-7.5s9 3.358 9 7.5z" />
                        </svg></span>
                    <div>
                        <h3 class="font-bold">Canales de atención</h3>
                        <p class="mt-1 text-xs text-neza-muted">Comunícate únicamente por medios oficiales.</p>
                    </div>
                </div>
                @if (filled($canalesAyuda?->telefono_contacto))
                    <div class="mt-5 rounded-xl bg-neza-wine-soft p-4">
                        <p class="text-[10px] font-bold uppercase text-neza-wine">Atención telefónica</p><a
                            href="tel:{{ preg_replace('/\D+/', '', $canalesAyuda->telefono_contacto) }}" class="mt-1 block text-xl font-bold text-[#340c1b]">{{ $canalesAyuda->telefono_contacto }}</a>
                    </div>
                @endif
                <div class="mt-4 space-y-3 text-[13px]">
                    @if (filled($canalesAyuda?->horario_atencion))
                        <p class="flex gap-3"><svg class="mt-0.5 size-4 shrink-0 text-neza-wine" aria-hidden="true"
                            fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg><span><strong class="block">Horario de atención</strong><span class="text-neza-muted">{{ $canalesAyuda->horario_atencion }}</span></span></p>
                    @endif
                    @if (filled($canalesAyuda?->email_contacto))
                        <p class="flex gap-3"><svg class="mt-0.5 size-4 shrink-0 text-neza-wine" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5A2.25 2.25 0 0119.5 19.5h-15A2.25 2.25 0 012.25 17.25V6.75M21.75 6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.917l-7.5 4.616a2.25 2.25 0 01-2.36 0L3.32 8.91A2.25 2.25 0 012.25 6.993V6.75" /></svg><span><strong class="block">Correo electrónico</strong><a href="mailto:{{ $canalesAyuda->email_contacto }}" class="text-neza-muted hover:text-neza-wine">{{ $canalesAyuda->email_contacto }}</a></span></p>
                    @endif
                </div>
                @if (filled($canalesAyuda?->redes_sociales))
                    <div class="mt-5 border-t border-neza-line pt-4"><p class="text-xs font-bold text-neza-ink">Redes sociales oficiales</p><div class="mt-3 flex flex-wrap gap-2">@foreach ($canalesAyuda->redes_sociales as $red)<a href="{{ $red['url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg border border-neza-line px-3 py-2 text-xs font-semibold text-neza-wine hover:bg-neza-wine-soft"><img src="{{ asset('images/social/'.strtolower($red['nombre']).'.svg') }}" alt="" class="size-4 object-contain">{{ $red['nombre'] }}</a>@endforeach</div></div>
                @endif
                <p class="mt-4 rounded-xl bg-[#fdfbf8] p-3 text-[11px] leading-relaxed text-[#76531f]">No compartas
                    contraseñas ni datos bancarios por mensajes o llamadas.</p>
            </aside>
        </div>
    </section>

    <section class="bg-white px-4 py-14 sm:px-6 lg:px-[72px] lg:py-[68px]">
        <div
            class="mx-auto flex min-h-[220px] max-w-[1296px] flex-col justify-between gap-8 overflow-hidden rounded-2xl bg-neza-wine px-6 py-9 text-white sm:px-10 lg:flex-row lg:items-center lg:px-12 lg:py-10">
            <div class="max-w-3xl">
                <p class="text-xs font-bold uppercase tracking-wide text-[#be985c]">Tu tiempo importa</p>
                <h2 class="mt-3 text-3xl font-bold sm:text-[38px]">Realiza tus trámites sin filas</h2>
                <p class="mt-3 text-[15px] text-white/70">Explora el catálogo, prepara tus documentos y da seguimiento con
                    tu CURP y token temporal.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row"><a href="{{ route('portal.tramites.index') }}"
                    class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-white px-5 text-sm font-bold text-[#340c1b] hover:bg-[#fdfbf8]"><svg
                        class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m21 21-4.35-4.35m1.35-5.4a6.75 6.75 0 11-13.5 0 6.75 6.75 0 0113.5 0z" />
                    </svg>Explorar trámites</a><a href="#consulta"
                    class="inline-flex h-12 items-center justify-center gap-2 rounded-xl border border-white/45 px-5 text-sm font-bold text-white hover:bg-white/10"><svg
                        class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                    </svg>Consultar trámite</a></div>
        </div>
    </section>
@endsection
