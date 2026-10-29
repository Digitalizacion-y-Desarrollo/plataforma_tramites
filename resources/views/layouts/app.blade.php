<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('images/logo/favicon-neza-2025-2027.png') }}">
    <link rel="apple-touch-icon" sizes="512x512" href="{{ asset('images/logo/favicon-neza-2025-2027.png') }}">
    @php
        $tituloRuta = match (true) {
            request()->routeIs('admin.dashboard') => 'Dashboard',
            request()->routeIs('solicitudes.*') => 'Solicitudes',
            request()->routeIs('formulario.*') => 'Formularios',
            request()->routeIs('catalogo-tramites.*') => 'Catálogo de trámites',
            request()->routeIs('catalogo-estados*') => 'Catálogos',
            request()->routeIs('auditoria.*') => 'Auditoría',
            request()->routeIs('configuracion-general.*') => 'Configuración general',
            request()->routeIs('canales-ayuda.*') => 'Canales de ayuda',
            request()->routeIs('portada.*') => 'Foto de portada',
            request()->routeIs('servicios-interes.*') => 'Servicios de interés',
            request()->routeIs('dependencias.*') => 'Dependencias',
            default => 'Portal del Servidor Público',
        };
    @endphp
    <title>@yield('title', $tituloRuta.' · '.config('app.name', 'Portal de Trámites'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body data-swal-success="{{ session('success') }}" data-swal-error="{{ $errors->any() ? $errors->first() : '' }}">
    @php
        $accesosRoles = collect(session('accesos.roles', session('accesos.role', [])))->flatten()->map(fn ($role) => mb_strtolower(trim((string) $role)));
        $isSuperuser = $accesosRoles->contains('superuser');
        $isSuperadmin = $accesosRoles->contains('superadmin');
        $isColaborador = ! $isSuperuser && ! $isSuperadmin && $accesosRoles->contains('colaborador');
        $rutaBusquedaSolicitudes = $isColaborador && filled(session('accesos.departamento_accesos_id'))
            ? route('areas.solicitudes.index', session('accesos.departamento_accesos_id'))
            : route('solicitudes.index');
    @endphp
    <div class="shell">
        <div class="fixed inset-0 z-40 hidden bg-neza-ink/40 min-[901px]:hidden" data-admin-menu-overlay></div>
        <aside id="admin-sidebar" class="sidebar sticky top-0 h-dvh overflow-x-hidden overflow-y-auto" aria-label="Menú de administración">
            <div class="flex flex-col items-center gap-1 px-2 text-center">
                <img src="{{ asset('images/logo/logo-sin-fondo.png') }}" alt="Logo Institucional" class="h-auto w-24">
                <span class="text-sm font-bold leading-tight text-neza-ink">Portal de Trámites</span>
                <span class="text-xs font-medium leading-tight text-neza-muted">Portal del Servidor Público</span>
            </div>
            <nav class="nav min-w-0 grid-cols-[minmax(0,1fr)] [&_details]:min-w-0 [&_details_div]:min-w-0 [&_details_div]:grid-cols-[minmax(0,1fr)] [&_details_div_a]:block [&_details_div_a]:min-w-0 [&_details_div_a]:truncate" aria-label="Navegación principal">
                <span class="side-label">Principal</span>
                @if (! $isColaborador)
                <a @class(['active' => request()->routeIs('admin.dashboard')]) href="{{ route('admin.dashboard') }}"><svg class="nav-icon" aria-hidden="true" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m2.25 12 8.954-8.955a1.125 1.125 0 0 1 1.592 0L21.75 12M4.5 9.75V19.5a1.5 1.5 0 0 0 1.5 1.5h3.75v-6.75h4.5V21H18a1.5 1.5 0 0 0 1.5-1.5V9.75" />
                    </svg>Dashboard</a>
                <a href="{{ route('solicitudes.index') }}" @class(['active' => request()->routeIs('solicitudes.*')])><svg class="nav-icon" aria-hidden="true" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 3.75H18a2.25 2.25 0 0 1 2.25 2.25v12A2.25 2.25 0 0 1 18 20.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75h2.25m0 0A2.25 2.25 0 0 0 10.5 6h3a2.25 2.25 0 0 0 2.25-2.25m-7.5 0A2.25 2.25 0 0 1 10.5 1.5h3a2.25 2.25 0 0 1 2.25 2.25m-7.5 9h7.5m-7.5 3h7.5m-7.5-6h7.5" />
                    </svg>Solicitudes</a>
                @elseif (filled(session('accesos.departamento_accesos_id')))
                <details class="group" open>
                    <summary class="flex min-w-0 cursor-pointer list-none items-center gap-[11px] rounded-[10px] py-[11px] pl-3 pr-5 text-[13px] font-medium text-[#667085] hover:bg-neza-wine-soft hover:text-neza-wine [&::-webkit-details-marker]:hidden{{ request()->routeIs('areas.*') ? ' active' : '' }}"><span class="min-w-0 flex-1 truncate text-left" title="{{ session('accesos.departamento') }}">{{ session('accesos.departamento') }}</span><svg class="size-4 shrink-0 transition-transform group-open:rotate-180" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg></summary>
                    <div class="mt-1 grid gap-1 border-l border-neza-line pl-3 pr-3"><a href="{{ route('areas.resumen', session('accesos.departamento_accesos_id')) }}" @class(['!py-2 !text-xs', 'active' => request()->routeIs('areas.resumen')])>Resumen</a><a href="{{ route('areas.solicitudes.index', session('accesos.departamento_accesos_id')) }}" @class(['!py-2 !text-xs', 'active' => request()->routeIs('areas.solicitudes.*')])>Solicitudes</a></div>
                </details>
                @endif
                @if ($isSuperuser)
                <details class="group" @if(request()->routeIs('dependencias.*') && request()->route('dependencia') === 'desarrollo-urbano') open @endif>
                    <summary class="flex min-w-0 cursor-pointer list-none items-center gap-[11px] rounded-[10px] py-[11px] pl-3 pr-5 text-[13px] font-medium text-[#667085] hover:bg-neza-wine-soft hover:text-neza-wine [&::-webkit-details-marker]:hidden{{ request()->routeIs('dependencias.*') && request()->route('dependencia') === 'desarrollo-urbano' ? ' active' : '' }}"><span class="min-w-0 flex-1 truncate text-left" title="Desarrollo Urbano">Desarrollo Urbano</span><svg class="size-4 shrink-0 transition-transform group-open:rotate-180" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg></summary>
                    <div class="mt-1 grid gap-1 border-l border-neza-line pl-3"><a href="{{ route('dependencias.resumen', 'desarrollo-urbano') }}" @class(['!py-2 !text-xs', 'active' => request()->routeIs('dependencias.resumen') && request()->route('dependencia') === 'desarrollo-urbano'])>Resumen</a><a href="{{ route('dependencias.solicitudes.index', 'desarrollo-urbano') }}" @class(['!py-2 !text-xs', 'active' => request()->routeIs('dependencias.solicitudes.*') && request()->route('dependencia') === 'desarrollo-urbano'])>Solicitudes</a></div>
                </details>
                <details class="group" @if(request()->routeIs('dependencias.*') && request()->route('dependencia') === 'secretaria-del-ayuntamiento') open @endif>
                    <summary class="flex min-w-0 cursor-pointer list-none items-center gap-[11px] rounded-[10px] py-[11px] pl-3 pr-5 text-[13px] font-medium text-[#667085] hover:bg-neza-wine-soft hover:text-neza-wine [&::-webkit-details-marker]:hidden{{ request()->routeIs('dependencias.*') && request()->route('dependencia') === 'secretaria-del-ayuntamiento' ? ' active' : '' }}"><span class="min-w-0 flex-1 truncate text-left" title="Secretaría del Ayuntamiento">Secretaría del Ayuntamiento</span><svg class="size-4 shrink-0 transition-transform group-open:rotate-180" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg></summary>
                    <div class="mt-1 grid gap-1 border-l border-neza-line pl-3"><a href="{{ route('dependencias.resumen', 'secretaria-del-ayuntamiento') }}" @class(['!py-2 !text-xs', 'active' => request()->routeIs('dependencias.resumen') && request()->route('dependencia') === 'secretaria-del-ayuntamiento'])>Resumen</a><a href="{{ route('dependencias.solicitudes.index', 'secretaria-del-ayuntamiento') }}" @class(['!py-2 !text-xs', 'active' => request()->routeIs('dependencias.solicitudes.*') && request()->route('dependencia') === 'secretaria-del-ayuntamiento'])>Solicitudes</a></div>
                </details>
                @endif
                @if ($isSuperadmin || $isSuperuser)
                <span class="side-label" style="margin-top:16px">Administración</span>
                @endif
                @if ($isSuperuser)
                <a href="{{ route('formulario.index') }}" @class(['active' => request()->routeIs('formulario.*')])><svg class="nav-icon" aria-hidden="true" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5V6.75a3.375 3.375 0 0 0-3.375-3.375h-3.75A3.375 3.375 0 0 0 4.125 6.75v10.5a3.375 3.375 0 0 0 3.375 3.375h8.25" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 18.75h6m-3-3v6" />
                    </svg>Formularios</a>
                <a href="{{ route('catalogo-tramites.index') }}" @class(['active' => request()->routeIs('catalogo-tramites.*')])><svg class="nav-icon" aria-hidden="true" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                    </svg>Trámites</a>
                <details class="group" @if (request()->routeIs('catalogo-*')) open @endif>
                    <summary
                        class="flex cursor-pointer list-none items-center gap-[11px] rounded-[10px] px-3 py-[11px] text-[13px] font-medium text-[#667085] outline-none transition hover:bg-neza-wine-soft hover:text-neza-wine focus-visible:ring-2 focus-visible:ring-neza-wine [&::-webkit-details-marker]:hidden{{ request()->routeIs('catalogo-*') ? ' active' : '' }}">
                        <svg class="nav-icon" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4.5 4.5h15v15h-15zM12 6.75v10.5M6.75 12h10.5" />
                        </svg>
                        <span>Catálogos</span>
                        <svg class="ml-auto size-4 transition-transform group-open:rotate-180" aria-hidden="true"
                            fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </summary>
                    <div class="mt-1 grid gap-1 border-l border-neza-line pl-3">
                        <a href="{{ route('catalogo-estados.index') }}" @class(['!py-2 !text-xs', 'active' => request()->routeIs('catalogo-estados.*')])>Estados de solicitudes</a>
                        <a href="{{ route('catalogo-estados-formularios.index') }}" @class(['!py-2 !text-xs', 'active' => request()->routeIs('catalogo-estados-formularios.*')])>Estados de formularios</a>
                        <a href="{{ route('catalogo-estados-documentos.index') }}" @class(['!py-2 !text-xs', 'active' => request()->routeIs('catalogo-estados-documentos.*')])>Estados de documentos</a>
                        <a href="{{ route('catalogo-estados-notificaciones.index') }}" @class(['!py-2 !text-xs', 'active' => request()->routeIs('catalogo-estados-notificaciones.*')])>Estados de notificaciones</a>
                    </div>
                </details>
                @endif
                @if ($isSuperadmin || $isSuperuser)
                <details class="group" @if (request()->routeIs('servicios-interes.*') || request()->routeIs('canales-ayuda.*') || request()->routeIs('configuracion-general.*')) open @endif>
                    <summary
                        class="flex cursor-pointer list-none items-center gap-[11px] rounded-[10px] px-3 py-[11px] text-[13px] font-medium text-[#667085] outline-none transition hover:bg-neza-wine-soft hover:text-neza-wine focus-visible:ring-2 focus-visible:ring-neza-wine [&::-webkit-details-marker]:hidden{{ request()->routeIs('servicios-interes.*') || request()->routeIs('canales-ayuda.*') || request()->routeIs('configuracion-general.*') ? ' active' : '' }}">
                    <svg class="nav-icon" aria-hidden="true" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.592c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.655.87.11.059.218.122.324.19.33.208.746.269 1.107.128l1.228-.478a1.125 1.125 0 0 1 1.285.454l1.296 2.244a1.125 1.125 0 0 1-.171 1.354l-1.015.859c-.282.239-.419.611-.386.98.007.131.007.263 0 .394.033.369.104.741.386.98l1.015.86c.407.344.514.917.17 1.353l-1.295 2.244a1.125 1.125 0 0 1-1.285.454l-1.228-.478c-.361-.14-.776-.08-1.107.128-.106.068-.213.131-.324.19-.342.184-.592.496-.655.87l-.213 1.281c-.09.542-.56.94-1.11.94h-2.592c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.063-.374-.313-.686-.655-.87a6.52 6.52 0 0 1-.324-.19c-.33-.208-.746-.269-1.107-.128l-1.228.478a1.125 1.125 0 0 1-1.285-.454l-1.296-2.244a1.125 1.125 0 0 1 .171-1.354l1.015-.859c.282-.239.419-.611.386-.98a6.932 6.932 0 0 1 0-.394c-.033-.369-.104-.741-.386-.98l-1.015-.86a1.125 1.125 0 0 1-.17-1.353l1.295-2.244a1.125 1.125 0 0 1 1.285-.454l1.228.478c.361.14.776.08 1.107-.128.106-.068.213-.131.324-.19.342-.184.592-.496.655-.87l.213-1.281Z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                    </svg>
                    <span>Configuración</span>
                    <svg class="ml-auto size-4 transition-transform group-open:rotate-180" aria-hidden="true" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                    </summary>
                    <div class="mt-1 grid gap-1 border-l border-neza-line pl-3">
                        @if ($isSuperuser)
                        <a href="{{ route('configuracion-general.edit') }}" @class(['!py-2 !text-xs', 'active' => request()->routeIs('configuracion-general.*')])>Configuración general</a>
                        @endif
                        <a href="{{ route('canales-ayuda.edit') }}" @class(['!py-2 !text-xs', 'active' => request()->routeIs('canales-ayuda.*')])>Canales de ayuda</a>
                        <a href="{{ route('portada.edit') }}" @class(['!py-2 !text-xs', 'active' => request()->routeIs('portada.*')])>Foto de portada</a>
                        <a href="{{ route('servicios-interes.index') }}" @class(['!py-2 !text-xs', 'active' => request()->routeIs('servicios-interes.*')])>Servicios y enlaces de interés</a>
                        <a href="{{ route('solicitudes.areas.index') }}" @class(['!py-2 !text-xs', 'active' => request()->routeIs('solicitudes.areas.*')])>Áreas operativas</a>
                    </div>
                </details>
                @endif
                @if ($isSuperadmin || $isSuperuser)
                <a href="{{ route('auditoria.index') }}" @class(['active' => request()->routeIs('auditoria.*')])><svg class="nav-icon" aria-hidden="true" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                    </svg>Auditoría</a>
                @endif
            </nav>
            <div class="side-footer truncate" title="Municipio de Nezahualcóyotl">Municipio de Nezahualcóyotl</div>
        </aside>
        <div class="workspace">
            <header class="topbar">
                <div class="tools">
                    <button type="button" class="inline-flex size-10 items-center justify-center rounded-xl border border-neza-line bg-white text-neza-wine transition hover:bg-neza-wine-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine min-[901px]:hidden" data-admin-menu-toggle aria-expanded="false" aria-controls="admin-sidebar">
                        <span class="sr-only">Abrir menú de administración</span>
                        <svg class="size-6" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                    <form action="{{ $rutaBusquedaSolicitudes }}" method="GET" role="search" class="search">
                        <svg class="size-5 shrink-0" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" />
                        </svg>
                        <label for="busqueda-global" class="sr-only">Buscar solicitudes por folio, CURP o correo</label>
                        <input id="busqueda-global" type="search" name="q" value="{{ request()->routeIs('solicitudes.*') ? request('q') : '' }}"
                            placeholder="Buscar por folio, CURP o correo"
                            class="min-w-0 flex-1 bg-transparent text-sm text-neza-ink placeholder:text-neza-muted focus:outline-none">
                        <button type="submit" class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg text-neza-wine hover:bg-neza-wine-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine" aria-label="Buscar solicitudes">
                            <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" />
                            </svg>
                        </button>
                    </form>
                </div>
                <div class="flex shrink-0 items-center gap-4">
                    <p class="whitespace-nowrap text-sm font-semibold text-neza-muted">Hola, <span class="font-bold text-neza-ink">{{ session('accesos.nombre') }}</span></p>
                    <button type="button" title="Abrir opciones de accesibilidad" data-accessibility-open aria-haspopup="dialog" aria-controls="accessibility-dialog" aria-label="Abrir opciones de accesibilidad" class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl border border-neza-line bg-white text-neza-wine transition hover:bg-neza-wine-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neza-wine">
                        <span class="sr-only">Abrir opciones de accesibilidad</span>
                        <svg class="size-6" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                        </svg>
                    </button>
                    <form method="POST" action="{{ route('accesos.logout') }}">@csrf<button type="submit" title="Cerrar sesión" data-submit-lock aria-label="Cerrar sesión" class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl border border-neza-line bg-white text-neza-wine transition hover:bg-neza-wine-soft">
                        <span class="sr-only">Cerrar sesión</span>
                        <svg class="size-6" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" />
                        </svg>
                    </button></form>
                </div>
            </header>
            <main>@yield('content')</main>
        </div>
    </div>
    @include('partials.accesibilidad')
</body>

</html>
