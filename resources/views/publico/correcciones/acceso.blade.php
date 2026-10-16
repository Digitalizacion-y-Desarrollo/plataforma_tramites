@extends('layouts.publico')

@section('title', 'Acceso para corrección de documentos')

@section('content')
    <section class="bg-neza-canvas px-4 py-12 sm:px-6 lg:px-[72px]">
        <div class="mx-auto max-w-lg rounded-2xl border border-neza-line bg-white p-6 shadow-sm sm:p-8">
            <p class="text-xs font-bold uppercase tracking-wide text-neza-wine">Corrección de documentos</p>
            <h1 class="mt-2 text-3xl font-bold text-neza-ink">Ingresa tu contraseña temporal</h1>
            <p class="mt-3 text-sm text-neza-muted">La contraseña fue enviada al correo registrado en tu solicitud. Tendrás hasta cinco intentos para ingresarla correctamente.</p>
            <form action="{{ route('correcciones.verificar', $token) }}" method="POST" class="mt-6" novalidate>
                @csrf
                <label for="password" class="block text-sm font-semibold text-neza-ink">Contraseña temporal</label>
                <input id="password" name="password" type="password" required autofocus autocomplete="one-time-code" class="mt-2 h-11 w-full rounded-lg border border-neza-line px-3 text-sm focus:border-neza-wine focus:outline-none focus:ring-2 focus:ring-neza-wine-soft @error('password') border-rose-600 @enderror">
                @error('password')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                <button type="submit" class="mt-5 rounded-lg bg-neza-wine px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Continuar</button>
            </form>
        </div>
    </section>
@endsection
