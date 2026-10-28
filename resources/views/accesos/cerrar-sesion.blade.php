<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cerrar sesión</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-neza-canvas p-5 font-sans text-neza-ink">
    <main class="w-full max-w-md rounded-2xl border border-neza-line bg-white p-7 shadow-lg">
        <img src="{{ asset('images/logo/logo-sin-fondo.png') }}" alt="Municipio de Nezahualcóyotl" class="mx-auto h-auto w-24">
        <h1 class="mt-5 text-center text-2xl font-bold">Cerrar sesión</h1>
        <p class="mt-2 text-center text-sm text-neza-muted">Usa esta opción si una pantalla de administración no te permite regresar o cerrar sesión normalmente.</p>
        <form method="POST" action="{{ route('accesos.logout') }}" class="mt-6">
            @csrf
            <button type="submit" data-submit-lock class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white">Cerrar sesión y volver al acceso</button>
        </form>
    </main>
</body>
</html>
