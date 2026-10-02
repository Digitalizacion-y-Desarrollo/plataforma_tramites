<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Trámite') · Nezahualcóyotl</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f5f6f7] font-sans text-neza-ink antialiased">
    <header class="border-b border-neza-line bg-white">
        <div class="mx-auto flex min-h-[90px] max-w-[1296px] items-center justify-between gap-5 px-4 py-3 sm:px-6">
            <a href="{{ route('portal.publico') }}" class="flex items-center gap-3"><img src="{{ asset('images/portal/logo-nezahualcoyotl.png') }}" alt="Gobierno Municipal de Nezahualcóyotl" class="h-16 w-20 object-contain"><span class="hidden sm:block"><strong class="block text-base">Plataforma de Trámites</strong><span class="text-xs text-neza-muted">Gobierno Municipal de Nezahualcóyotl</span></span></a>
            <nav class="flex items-center gap-2 text-xs font-bold"><a href="{{ route('portal.tramites.predial') }}" class="hidden rounded-xl px-4 py-3 text-neza-wine hover:bg-neza-wine-soft sm:block">Guardar y salir</a><span class="hidden items-center gap-2 rounded-xl bg-[#edf8f1] px-4 py-3 text-[#287a47] md:flex"><svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 00-9 0v3.75m-.75 0h10.5A2.25 2.25 0 0119.5 12.75v6A2.25 2.25 0 0117.25 21H6.75a2.25 2.25 0 01-2.25-2.25v-6a2.25 2.25 0 012.25-2.25z" /></svg>Sesión segura</span><a href="{{ route('portal.publico') }}#ayuda" class="rounded-xl border border-neza-line px-4 py-3">Ayuda</a></nav>
        </div>
    </header>
    <main>@yield('content')</main>
    <footer class="bg-[#340c1b] text-white"><div class="mx-auto grid max-w-[1296px] gap-5 px-4 py-10 sm:grid-cols-3 sm:px-6">@foreach ([['Conexión segura', 'La información viaja cifrada y protegida.'], ['Protección de datos', 'Usamos tus datos solo para este trámite.'], ['Asistencia', 'Llámanos al 55 5716 9070']] as [$titulo, $texto])<div class="sm:border-l sm:border-white/15 sm:pl-7 first:border-0 first:pl-0"><p class="text-sm font-bold">{{ $titulo }}</p><p class="mt-1 text-xs text-white/65">{{ $texto }}</p></div>@endforeach</div><div class="bg-white text-neza-ink"><div class="mx-auto flex max-w-[1296px] flex-col gap-3 px-4 py-8 text-xs sm:flex-row sm:items-center sm:justify-between sm:px-6"><strong>© {{ date('Y') }} Gobierno Municipal de Nezahualcóyotl</strong><div class="flex gap-5 text-neza-muted"><a href="#">Privacidad</a><a href="#">Términos</a><a href="#">Accesibilidad</a></div></div></div></footer>
</body>
</html>
