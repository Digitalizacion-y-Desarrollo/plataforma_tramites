<footer id="dependencias" class="bg-[#340c1b] text-white">
    <div class="mx-auto grid max-w-[1440px] gap-12 px-4 py-14 sm:px-6 lg:grid-cols-[330px_1fr] lg:gap-16 lg:px-[72px] lg:py-16">
        <div class="space-y-5">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo/logo-footer-transparente.png') }}" alt="Logo del Gobierno Municipal de Nezahualcóyotl" class="h-auto w-48 max-w-full object-contain">
                <div>
                    <p class="text-[13px] font-bold">Gobierno Municipal de Nezahualcóyotl</p>
                    <p class="mt-1 text-[11px] text-white/70">Plataforma de Trámites</p>
                </div>
            </div>
            <p class="max-w-sm text-[13px] leading-relaxed text-white/70">Portal público para consultar información y acceder a trámites y servicios municipales.</p>
            <div class="space-y-2 text-[13px]">
                @if (filled($canalesAyuda?->telefono_contacto))
                <a href="tel:{{ preg_replace('/\D+/', '', $canalesAyuda->telefono_contacto) }}" class="flex items-center gap-2 font-bold hover:text-[#be985c]">
                    <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102A1.125 1.125 0 005.872 2.25H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                    {{ $canalesAyuda->telefono_contacto }}
                </a>
                @endif
                @if (filled($canalesAyuda?->horario_atencion))
                <p class="flex items-center gap-2 text-white/70">
                    <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ $canalesAyuda->horario_atencion }}
                </p>
                @endif
                @if (filled($canalesAyuda?->email_contacto))
                    <a href="mailto:{{ $canalesAyuda->email_contacto }}" class="flex items-center gap-2 text-white/70 hover:text-[#be985c]">
                        <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5A2.25 2.25 0 0119.5 19.5h-15A2.25 2.25 0 012.25 17.25V6.75M21.75 6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.917l-7.5 4.616a2.25 2.25 0 01-2.36 0L3.32 8.91A2.25 2.25 0 012.25 6.993V6.75" /></svg>
                        {{ $canalesAyuda->email_contacto }}
                    </a>
                @endif
            </div>
        </div>

        <nav class="grid gap-x-10 gap-y-8 sm:gap-x-16 lg:justify-self-end" aria-label="Enlaces del pie de página">
            <div>
                <h2 class="text-[13px] font-bold text-[#be985c]">Portal</h2>
                <ul class="mt-4 space-y-2.5 text-[13px] text-white/70">
                    <li><a href="{{ route('portal.publico') }}" class="transition hover:text-white">Inicio</a></li>
                    <li><a href="{{ route('portal.tramites.index') }}" class="transition hover:text-white">Trámites</a></li>
                    <li><a href="{{ request()->routeIs('portal.publico') ? '#servicios' : route('portal.publico').'#servicios' }}" class="transition hover:text-white">Servicios</a></li>
                    <li><a href="{{ route('portal.publico') }}#consulta" class="transition hover:text-white">Consulta de solicitudes</a></li>
                    <li><a href="{{ route('portal.aviso-privacidad') }}" class="transition hover:text-white">Aviso de privacidad</a></li>
                    <li>
                        <button type="button" data-accessibility-open aria-haspopup="dialog" aria-controls="accessibility-dialog" class="text-left text-white/70 transition hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Accesibilidad</button>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-[1440px] flex-col gap-4 px-4 py-6 text-xs text-white/70 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-[72px]">
            <p>© {{ date('Y') }} Gobierno Municipal de Nezahualcóyotl · Portal de trámites</p>
            <button type="button" data-accessibility-open aria-haspopup="dialog" aria-controls="accessibility-dialog" class="self-start hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Accesibilidad</button>
            @if (filled($canalesAyuda?->redes_sociales))
                <div class="flex items-center gap-2" aria-label="Redes sociales">
                    <span>Síguenos</span>
                    @foreach ($canalesAyuda->redes_sociales as $red)
                        <a href="{{ $red['url'] }}" target="_blank" rel="noopener noreferrer" class="grid size-8 place-items-center rounded-full border border-white/20 bg-white transition hover:bg-[#fdfbf8] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" aria-label="{{ $red['nombre'] }}"><img src="{{ asset('images/social/'.strtolower($red['nombre']).'.svg') }}" alt="" class="size-4 object-contain"></a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</footer>
