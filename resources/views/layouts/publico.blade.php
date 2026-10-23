<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Portal de trámites y servicios del Gobierno Municipal de Nezahualcóyotl.">
    <link rel="icon" type="image/png" href="{{ asset('images/logo/logo-sin-fondo.png') }}">
    <title>@yield('title', 'Portal de Trámites') · Nezahualcóyotl</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neza-canvas font-sans text-neza-ink antialiased" data-solicitud-activa='@json(session('solicitud_activa'))' data-swal-success="{{ session('success') }}" data-swal-error="{{ $errors->any() ? $errors->first() : '' }}">
    @include('partials.publico.header')

    <main>
        @yield('content')
    </main>

    @include('partials.publico.footer')
    @include('partials.accesibilidad')
</body>
</html>
