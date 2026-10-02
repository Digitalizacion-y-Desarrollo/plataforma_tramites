<footer id="dependencias" class="bg-[#340c1b] text-white">
    <div class="mx-auto grid max-w-[1440px] gap-12 px-4 py-14 sm:px-6 lg:grid-cols-[330px_1fr] lg:gap-16 lg:px-[72px] lg:py-16">
        <div class="space-y-5">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/portal/logo-nezahualcoyotl.png') }}" alt="" class="h-[72px] w-[102px] object-contain">
                <div>
                    <p class="text-[13px] font-bold">Gobierno Municipal de Nezahualcóyotl</p>
                    <p class="mt-1 text-[11px] text-white/70">Plataforma de Trámites</p>
                </div>
            </div>
            <p class="max-w-sm text-[13px] leading-relaxed text-white/70">Portal público para consultar información y acceder a trámites y servicios municipales.</p>
            <div class="space-y-2 text-[13px]">
                <a href="tel:5557169070" class="flex items-center gap-2 font-bold hover:text-[#be985c]">
                    <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102A1.125 1.125 0 005.872 2.25H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                    55 5716 9070
                </a>
                <p class="flex items-center gap-2 text-white/70">
                    <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Atención en horario institucional
                </p>
            </div>
        </div>

        @php
            $footerLinks = [
                'Portal' => ['Inicio' => route('portal.publico').'#inicio', 'Trámites' => route('portal.tramites.index'), 'Servicios' => route('portal.publico').'#servicios', 'Dependencias' => route('portal.publico').'#dependencias'],
                'Información' => ['Transparencia' => '#avisos', 'Avisos' => '#avisos', 'Convocatorias' => '#avisos', 'Directorio' => '#dependencias'],
                'Soporte' => ['Centro de ayuda' => '#ayuda', 'Preguntas frecuentes' => '#ayuda', 'Accesibilidad' => '#inicio', 'Quejas y sugerencias' => '#ayuda'],
                'Legal' => ['Aviso de privacidad' => '#', 'Términos de uso' => '#', 'Protección de datos' => '#', 'Datos abiertos' => '#'],
            ];
        @endphp
        <nav class="grid grid-cols-2 gap-8 sm:grid-cols-4" aria-label="Enlaces del pie de página">
            @foreach ($footerLinks as $titulo => $enlaces)
                <div>
                    <h2 class="text-[13px] font-bold text-[#be985c]">{{ $titulo }}</h2>
                    <ul class="mt-4 space-y-2.5 text-[13px] text-white/70">
                        @foreach ($enlaces as $texto => $href)
                            <li><a href="{{ $href }}" class="transition hover:text-white">{{ $texto }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>
    </div>
    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-[1440px] flex-col gap-4 px-4 py-6 text-xs text-white/70 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-[72px]">
            <p>© {{ date('Y') }} Gobierno Municipal de Nezahualcóyotl · Portal de trámites</p>
            <div class="flex flex-wrap gap-x-5 gap-y-2">
                <a href="#" class="hover:text-white">Privacidad</a>
                <a href="#" class="hover:text-white">Accesibilidad</a>
                <a href="#" class="hover:text-white">Mapa del sitio</a>
            </div>
            <div class="flex items-center gap-2" aria-label="Redes sociales">
                <span>Síguenos</span>
                @foreach (['Facebook', 'Instagram', 'YouTube'] as $red)
                    <a href="#" class="grid size-8 place-items-center rounded-full border border-white/15 bg-white/5 font-bold transition hover:bg-white/15" aria-label="{{ $red }}">{{ mb_substr($red, 0, 1) }}</a>
                @endforeach
            </div>
        </div>
    </div>
</footer>
