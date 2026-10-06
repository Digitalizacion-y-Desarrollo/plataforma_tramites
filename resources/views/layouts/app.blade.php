<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo/logo-sin-fondo.png') }}">
    <title>{{ config('app.name', 'Portal de Trámites') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body data-swal-success="{{ session('success') }}" data-swal-error="{{ $errors->any() ? $errors->first() : '' }}">
    <div class="shell">
        <aside class="sidebar">
            <div class="brand flex flex-col">
                <span class="">
                    <img src="{{ asset('images/logo/logo-sin-fondo.png') }}" alt="Logo Institucional"
                        class="w-24 h-auto"> </span>
                <span class="text-sm text-neza-muted">
                    Portal de Trámites
                </span>
                Portal del Servidor Público
            </div>
            <nav class="nav" aria-label="Navegación principal">
                <span class="side-label">Principal</span>
                <a class="active" href="{{ url('/') }}"><svg class="nav-icon" aria-hidden="true" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m2.25 12 8.954-8.955a1.125 1.125 0 0 1 1.592 0L21.75 12M4.5 9.75V19.5a1.5 1.5 0 0 0 1.5 1.5h3.75v-6.75h4.5V21H18a1.5 1.5 0 0 0 1.5-1.5V9.75" />
                    </svg>Dashboard</a>
                <a href="{{ route('solicitudes.index') }}" @class(['active' => request()->routeIs('solicitudes.*')])><svg class="nav-icon" aria-hidden="true" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 3.75H18a2.25 2.25 0 0 1 2.25 2.25v12A2.25 2.25 0 0 1 18 20.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75h2.25m0 0A2.25 2.25 0 0 0 10.5 6h3a2.25 2.25 0 0 0 2.25-2.25m-7.5 0A2.25 2.25 0 0 1 10.5 1.5h3a2.25 2.25 0 0 1 2.25 2.25m-7.5 9h7.5m-7.5 3h7.5m-7.5-6h7.5" />
                    </svg>Solicitudes</a>
                <a href="#"><svg class="nav-icon" aria-hidden="true" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5V6.75a3.375 3.375 0 0 0-3.375-3.375h-3.75A3.375 3.375 0 0 0 4.125 6.75v10.5a3.375 3.375 0 0 0 3.375 3.375h4.125" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.5 14.25h3.75m0 0v3.75m0-3.75-4.5 4.5" />
                    </svg>Trámites</a>
                <a href="{{ route('formulario.index') }}"><svg class="nav-icon" aria-hidden="true" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5V6.75a3.375 3.375 0 0 0-3.375-3.375h-3.75A3.375 3.375 0 0 0 4.125 6.75v10.5a3.375 3.375 0 0 0 3.375 3.375h8.25" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 18.75h6m-3-3v6" />
                    </svg>Formularios</a>
                <span class="side-label" style="margin-top:16px">Administración</span>
                <details class="group">
                    <summary
                        class="flex cursor-pointer list-none items-center gap-[11px] rounded-[10px] px-3 py-[11px] text-[13px] font-medium text-[#667085] outline-none transition hover:bg-neza-wine-soft hover:text-neza-wine focus-visible:ring-2 focus-visible:ring-neza-wine [&::-webkit-details-marker]:hidden">
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
                        <a href="{{ route('catalogo-tramites.index') }}" class="!py-2 !text-xs">Trámites</a>
                        <a href="{{ route('catalogo-estados.index') }}" class="!py-2 !text-xs">Estados de solicitudes</a>
                        <a href="{{ route('catalogo-estados-formularios.index') }}" class="!py-2 !text-xs">Estados de formularios</a>
                        <a href="{{ route('catalogo-estados-documentos.index') }}" class="!py-2 !text-xs">Estados de documentos</a>
                        <a href="{{ route('catalogo-estados-notificaciones.index') }}" class="!py-2 !text-xs">Estados de notificaciones</a>
                    </div>
                </details>
                <a href="#"><svg class="nav-icon" aria-hidden="true" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.592c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.655.87.11.059.218.122.324.19.33.208.746.269 1.107.128l1.228-.478a1.125 1.125 0 0 1 1.285.454l1.296 2.244a1.125 1.125 0 0 1-.171 1.354l-1.015.859c-.282.239-.419.611-.386.98.007.131.007.263 0 .394.033.369.104.741.386.98l1.015.86c.407.344.514.917.17 1.353l-1.295 2.244a1.125 1.125 0 0 1-1.285.454l-1.228-.478c-.361-.14-.776-.08-1.107.128-.106.068-.213.131-.324.19-.342.184-.592.496-.655.87l-.213 1.281c-.09.542-.56.94-1.11.94h-2.592c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.063-.374-.313-.686-.655-.87a6.52 6.52 0 0 1-.324-.19c-.33-.208-.746-.269-1.107-.128l-1.228.478a1.125 1.125 0 0 1-1.285-.454l-1.296-2.244a1.125 1.125 0 0 1 .171-1.354l1.015-.859c.282-.239.419-.611.386-.98a6.932 6.932 0 0 1 0-.394c-.033-.369-.104-.741-.386-.98l-1.015-.86a1.125 1.125 0 0 1-.17-1.353l1.295-2.244a1.125 1.125 0 0 1 1.285-.454l1.228.478c.361.14.776.08 1.107-.128.106-.068.213-.131.324-.19.342-.184.592-.496.655-.87l.213-1.281Z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                    </svg>Configuración</a>
            </nav>
            <div class="side-footer">Municipio de Nezahualcóyotl</div>
        </aside>
        <div class="workspace">
            <header class="topbar">
                <div class="context"><span class="shield">♧</span>Administración institucional</div>
                <div class="tools">
                    <div class="search"><span>⌕</span><span>Buscar solicitudes, trámites, ciudadanos...</span><span
                            class="shortcut">⌘ K</span></div>
                    <div class="notification" aria-label="3 notificaciones">♧<span class="badge">3</span></div><span
                        class="divider"></span>
                    <div class="profile"><img src="{{ asset('images/figma/profile.jpeg') }}" alt="María López">
                        <div>
                            <div class="profile-name">María López</div>
                            <div class="profile-role">Servidor público</div>
                        </div>
                        <span class="chevron">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>

                        </span>
                    </div>
                </div>
            </header>
            <main>@yield('content')</main>
        </div>
    </div>
</body>

</html>
