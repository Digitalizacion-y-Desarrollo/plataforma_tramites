<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso al portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-neza-canvas p-5 font-sans text-neza-ink" data-swal-error="{{ $errors->any() ? $errors->first() : '' }}">
    <main class="w-full max-w-md rounded-2xl border border-neza-line bg-white p-7 shadow-lg">
        <img src="{{ asset('images/logo/logo-sin-fondo.png') }}" alt="Municipio de Nezahualcóyotl" class="mx-auto h-auto w-24">
        <h1 class="mt-5 text-center text-2xl font-bold">Portal del servidor público</h1>
        <p class="mt-2 text-center text-sm text-neza-muted">Ingresa con tus credenciales de Accesos.</p>
        <form method="POST" action="{{ route('accesos.login.store') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-semibold">Correo institucional</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="mt-2 w-full rounded-lg border border-neza-line px-3 py-2.5 text-sm @error('email') border-rose-600 @enderror">
                @error('email')
                    <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-semibold">Contraseña</label>
                <div class="relative mt-2">
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-lg border border-neza-line py-2.5 pl-3 pr-12 text-sm @error('password') border-rose-600 @enderror">
                    <button type="button" data-password-toggle aria-controls="password" aria-label="Mostrar contraseña" aria-pressed="false" class="absolute inset-y-0 right-0 inline-flex w-11 items-center justify-center text-neza-muted hover:text-neza-ink focus-visible:rounded-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                        <svg data-password-visible-icon class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                        <svg data-password-hidden-icon class="hidden size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 2.25 12s3.75 6.75 9.75 6.75c1.302 0 2.53-.318 3.647-.823M6.53 6.53A9.733 9.733 0 0 1 12 5.25c6 0 9.75 6.75 9.75 6.75a15.637 15.637 0 0 1-2.638 3.195M6.53 6.53 3 3m3.53 3.53 3.584 3.584m7.356 7.356L21 21m-3.53-3.53-3.584-3.584m-3.772-3.772a3 3 0 0 0 3.772 3.772" /></svg>
                    </button>
                </div>
                @error('password')
                    <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" data-submit-lock class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white">Iniciar sesión</button>
        </form>
    </main>
</body>
</html>
