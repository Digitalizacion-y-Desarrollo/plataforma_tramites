<header class="relative z-30 bg-white/95 shadow-sm backdrop-blur">
    <div data-public-notice class="bg-[#340c1b] text-white">
        <div class="mx-auto flex min-h-10 max-w-[1440px] items-center justify-between gap-4 px-4 py-2 sm:px-6 lg:px-[72px]">
            <div class="flex min-w-0 items-center gap-2 text-[11px] sm:text-[13px]">
                <svg class="size-4 shrink-0" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                <p class="truncate">Consulta requisitos y costos antes de iniciar. La información de cada trámite indica su modalidad vigente.</p>
                <a href="#avisos" class="hidden shrink-0 font-bold text-[#be985c] hover:text-white sm:inline">Ver recomendaciones</a>
            </div>
            <button type="button" data-dismiss-public-notice class="grid size-7 shrink-0 place-items-center rounded-lg text-white/80 transition hover:bg-white/10 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" aria-label="Cerrar aviso">
                <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div class="mx-auto flex min-h-[100px] max-w-[1440px] items-center justify-between gap-5 px-4 py-3 sm:px-6 lg:min-h-[124px] lg:px-[72px]">
        <a href="{{ route('portal.publico') }}" class="flex min-w-0 items-center gap-3" aria-label="Ir al inicio">
            <img src="{{ asset('images/portal/logo-nezahualcoyotl.png') }}" alt="Gobierno Municipal de Nezahualcóyotl" class="h-[70px] w-[96px] shrink-0 object-contain lg:h-[82px] lg:w-[112px]">
            <span class="hidden min-w-0 sm:block">
                <span class="block text-xs font-bold text-neza-wine lg:text-[13px]">Gobierno Municipal de</span>
                <span class="block truncate text-lg font-bold text-[#340c1b] lg:text-[19px]">Nezahualcóyotl</span>
                <span class="block text-[11px] text-neza-muted">Plataforma de Trámites</span>
            </span>
        </a>

        <nav class="hidden items-center gap-6 text-[13px] font-medium text-neza-ink xl:flex" aria-label="Navegación principal">
            <a href="#inicio" class="relative font-bold text-neza-wine after:absolute after:-bottom-2 after:left-1/2 after:h-0.5 after:w-6 after:-translate-x-1/2 after:rounded-full after:bg-[#be985c]">Inicio</a>
            <a href="{{ route('portal.tramites.index') }}" class="transition hover:text-neza-wine">Trámites</a>
            <a href="#servicios" class="transition hover:text-neza-wine">Servicios</a>
            <a href="#dependencias" class="transition hover:text-neza-wine">Dependencias</a>
            <a href="#avisos" class="transition hover:text-neza-wine">Transparencia</a>
            <a href="#ayuda" class="transition hover:text-neza-wine">Ayuda</a>
        </nav>

        <div class="flex items-center gap-2.5">
            <button type="button" class="hidden h-[42px] items-center gap-2 rounded-xl border border-[#ede7de] bg-[#fdfbf8] px-3 text-[13px] font-bold text-[#76531f] transition hover:bg-[#f7f1e8] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine md:flex">
                <svg class="size-[18px]" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5a3.75 3.75 0 117.5 0m-9 15.75l1.5-7.5 3.75 3 3.75-3 1.5 7.5M12 7.5v8.25" />
                </svg>
                Accesibilidad
            </button>
            <a href="#consulta" class="flex h-[42px] items-center gap-2 rounded-xl border border-neza-line bg-white px-3 text-[13px] font-bold text-neza-wine transition hover:border-neza-wine focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine lg:px-4">
                <svg class="size-[18px]" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                </svg>
                <span class="hidden sm:inline">Consultar trámite</span>
                <span class="sm:hidden">Consulta</span>
            </a>
        </div>
    </div>
</header>
