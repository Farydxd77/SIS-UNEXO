<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Inicio') - SIS UNEXO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Identidad inspirada en moodle.org: azul marino, naranja y titulos serif. */
        :root {
            --unexo: #1b3a57; --unexo-claro: #24507a; --unexo-suave: #eaf0f6;
            --naranja: #f98012; --naranja-oscuro: #c75f00; --crema: #fbf5ee; --borde: #e5e1db;
            --bs-primary: #1b3a57; --bs-primary-rgb: 27, 58, 87;
            --bs-link-color: #1f5f99; --bs-link-color-rgb: 31, 95, 153;
            --bs-link-hover-color: #1b3a57; --bs-link-hover-color-rgb: 27, 58, 87;
            --bs-body-color: #1d2125; --bs-border-color: var(--borde); --bs-border-radius: .5rem;
            --bs-body-font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            --alto-barra: 62px; --ancho-cajon: 270px;
        }
        body { background: #fff; font-size: .9375rem; }
        a { text-decoration: none; }
        a:hover { text-decoration: underline; }
        a.btn:hover, a.btn-accion:hover, a.nav-link:hover, a.enlace-cajon:hover, a.list-group-item:hover, a.card:hover, a.logo-unexo:hover, a.dropdown-item:hover { text-decoration: none; }
        .serif, h1, h2.text-unexo, .contenido-interior > h2, .contenido-interior h2.mb-0, .contenido-interior h2.mb-1 {
            font-family: 'DM Serif Display', Georgia, serif; font-weight: 400; color: var(--unexo); letter-spacing: -.01em;
        }
        h2.text-unexo, .contenido-interior h2.mb-0, .contenido-interior h2.mb-1 { font-size: 2rem; }
        h4.mb-0 { font-weight: 700; color: var(--unexo); }
        h5, .h5 { font-weight: 700; color: var(--unexo); }
        .text-unexo { color: var(--unexo) !important; }

        /* Botones: azul marino solido; secundarios con contorno, como moodle.org. */
        .btn { --bs-btn-font-weight: 500; }
        .btn-primary, .btn-unexo {
            --bs-btn-bg: var(--unexo); --bs-btn-border-color: var(--unexo); --bs-btn-color: #fff;
            --bs-btn-hover-bg: var(--unexo-claro); --bs-btn-hover-border-color: var(--unexo-claro); --bs-btn-hover-color: #fff;
            --bs-btn-active-bg: var(--unexo-claro); --bs-btn-active-border-color: var(--unexo-claro);
        }
        .btn-outline-primary, .btn-outline-secondary, .btn-outline-dark {
            --bs-btn-color: var(--unexo); --bs-btn-border-color: #c9d3dd; --bs-btn-bg: #fff;
            --bs-btn-hover-bg: var(--unexo-suave); --bs-btn-hover-border-color: var(--unexo); --bs-btn-hover-color: var(--unexo);
            --bs-btn-active-bg: var(--unexo-suave); --bs-btn-active-color: var(--unexo);
        }
        .btn-secondary { --bs-btn-bg: #e9ecef; --bs-btn-border-color: #e9ecef; --bs-btn-color: #1d2125; --bs-btn-hover-bg: #dee2e6; --bs-btn-hover-border-color: #dee2e6; --bs-btn-hover-color: #1d2125; }
        .btn-pastilla { border-radius: 2rem; padding-left: 1.25rem; padding-right: 1.25rem; }
        .text-bg-primary { background-color: var(--unexo) !important; }

        /* Barra superior blanca con el logo en minusculas, como el de Moodle. */
        .barra-superior { height: var(--alto-barra); background: #fff; border-bottom: 1px solid var(--borde); z-index: 1030; }
        .logo-unexo { display: inline-flex; align-items: flex-end; gap: .15rem; text-decoration: none; line-height: 1; }
        .logo-unexo .birrete { color: var(--unexo); font-size: 1.15rem; transform: translateY(-.55rem) rotate(-12deg); }
        .logo-unexo .marca { color: var(--naranja); font-weight: 800; font-size: 1.6rem; letter-spacing: -.04em; }
        .logo-cuadro { width: 56px; height: 56px; border-radius: .8rem; background: var(--crema); display: inline-flex; align-items: center; justify-content: center; }
        .nav-primaria .nav-link { color: var(--unexo); font-weight: 500; height: var(--alto-barra); display: flex; align-items: center; padding: 0 .9rem !important; border-bottom: 3px solid transparent; }
        .nav-primaria .nav-link:hover { color: var(--naranja-oscuro); }
        .nav-primaria .nav-link.active { border-bottom-color: var(--naranja); font-weight: 700; }
        .avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--crema); color: var(--naranja-oscuro); border: 1px solid #f3dcc4; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: .8rem; }
        .btn-icono { border: 0; background: transparent; width: 40px; height: 40px; border-radius: .5rem; font-size: 1.3rem; color: var(--unexo); }
        .btn-icono:hover { background: var(--crema); }

        /* Cajon lateral (indice), tono calido. */
        .cajon { width: var(--ancho-cajon); background: #faf8f5; border-right: 1px solid var(--borde); }
        @media (min-width: 992px) {
            .cajon { position: fixed; top: var(--alto-barra); bottom: 0; left: 0; overflow-y: auto; transition: transform .25s ease; }
            .contenido { margin-left: var(--ancho-cajon); transition: margin-left .25s ease; }
            body.cajon-cerrado .cajon { transform: translateX(-100%); }
            body.cajon-cerrado .contenido { margin-left: 0; }
        }
        .cajon .titulo-seccion { font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; color: #8a8178; font-weight: 700; padding: 1.1rem 1.1rem .35rem; }
        .cajon .enlace-cajon { display: flex; align-items: center; gap: .65rem; padding: .5rem 1.1rem; color: #1d2125; text-decoration: none; border-left: 3px solid transparent; }
        .cajon .enlace-cajon:hover { background: #f1ece5; }
        .cajon .enlace-cajon.active { background: #fff; border-left-color: var(--naranja); color: var(--unexo); font-weight: 600; }
        .cajon .enlace-cajon i { font-size: 1.05rem; width: 1.2rem; text-align: center; color: var(--unexo); }

        .contenido { padding-top: var(--alto-barra); min-height: 100vh; display: flex; flex-direction: column; }
        .contenido-interior { max-width: 1320px; width: 100%; margin: 0 auto; padding: 2rem 1.5rem; flex-grow: 1; }

        /* Cabecera de pagina de administracion: banda crema, titulo serif y pestanas. */
        .cabecera-pagina { background: var(--crema); border-bottom: 1px solid var(--borde); }
        .cabecera-pagina .interior { max-width: 1320px; margin: 0 auto; padding: 1.5rem 1.5rem 0; }
        .cabecera-pagina h1 { font-size: 2.4rem; margin: .25rem 0 1.1rem; }
        .nav-secundaria { gap: .25rem; flex-wrap: nowrap; overflow-x: auto; }
        .nav-secundaria .nav-link { color: var(--unexo); font-weight: 500; padding: .6rem .9rem; border-bottom: 3px solid transparent; white-space: nowrap; }
        .nav-secundaria .nav-link:hover { color: var(--naranja-oscuro); }
        .nav-secundaria .nav-link.active { border-bottom-color: var(--naranja); font-weight: 700; background: transparent; }
        .nav-terciaria { gap: 1.25rem; border-bottom: 1px solid var(--borde); margin-bottom: 1.5rem; }
        .nav-terciaria a { padding: .25rem 0 .6rem; color: #5b6670; text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -1px; }
        .nav-terciaria a:hover { color: var(--unexo); }
        .nav-terciaria a.active { color: var(--unexo); font-weight: 600; border-bottom-color: var(--unexo); }

        /* Migas de pan. */
        .migas { font-size: .85rem; }
        .migas .breadcrumb-item + .breadcrumb-item::before { content: '/'; color: #b8aea3; }
        .migas a { text-decoration: none; color: #1f5f99; }
        .migas a:hover { text-decoration: underline; }
        .migas .active { color: #6c757d; }

        /* Pie azul marino, como moodle.org. */
        .pie { background: var(--unexo); color: #c7d3df; font-size: .85rem; }
        .pie strong { color: #fff; }
        .pie .logo-unexo .marca { font-size: 1.2rem; }
        .pie .logo-unexo .birrete { color: #fff; font-size: .9rem; transform: translateY(-.4rem) rotate(-12deg); }

        /* Tarjetas planas con borde fino. */
        .card { border: 1px solid var(--borde); border-radius: .6rem; }
        .card.shadow-sm { box-shadow: none !important; }
        .card-header { background: #fff; font-weight: 700; color: var(--unexo); border-bottom-color: var(--borde); }
        .card-metrica { border-left: 4px solid var(--naranja); }
        form.card.card-body { background: #faf8f5; }
        /* Las tablas de Moodle van sueltas, sin marco de tarjeta. */
        .card:has(> .table-responsive) { border: 0; background: transparent; }

        /* Tablas tipo "generaltable" de Moodle. */
        .tabla-compacta td, .tabla-compacta th { padding: .7rem .75rem; }
        .table { --bs-table-hover-bg: #faf6f1; }
        .table > :not(caption) > * > * { border-bottom-color: #eee9e3; }
        .table > thead.table-light th { background: #fff; color: #5b6670; font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; font-weight: 700; border-bottom: 2px solid var(--borde); }
        .table td a:not(.btn) { text-decoration: none; font-weight: 600; }
        .table td a:not(.btn):hover { text-decoration: underline; }

        /* Acciones con icono, como el engranaje y el ojo de Moodle. */
        .acciones { display: inline-flex; align-items: center; gap: .15rem; }
        .btn-accion { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: .4rem; border: 0; background: transparent; color: var(--unexo); font-size: 1.05rem; text-decoration: none; }
        .btn-accion:hover { background: var(--unexo-suave); color: var(--unexo); }
        .btn-accion.peligro { color: #b42318; }
        .btn-accion.peligro:hover { background: #fdecea; }
        .btn-accion.exito { color: #1f7a4d; }
        .btn-accion.exito:hover { background: #e7f5ee; }

        .nav-tabs { border-bottom-color: var(--borde); }
        .nav-tabs .nav-link { color: #5b6670; }
        .nav-tabs .nav-link.active { color: var(--unexo); font-weight: 600; border-color: var(--borde) var(--borde) #fff; }
        .nav-pills .nav-link { color: var(--unexo); border: 1px solid #c9d3dd; border-radius: 2rem; }
        .nav-pills .nav-link.active { background: var(--unexo); border-color: var(--unexo); }

        /* Formularios. */
        .form-label { font-weight: 600; font-size: .9rem; color: #2c3640; }
        .form-control:focus, .form-select:focus { border-color: #9fb5ca; box-shadow: 0 0 0 .2rem rgba(27, 58, 87, .15); }
        .badge { font-weight: 600; }

        /* Tarjeta de curso de Moodle: cabecera de color + nombre + progreso. */
        .curso-tarjeta { overflow: hidden; transition: box-shadow .15s ease; }
        .curso-tarjeta:hover { box-shadow: 0 .25rem .75rem rgba(27, 58, 87, .08); }
        .curso-imagen { height: 110px; }
        .curso-categoria { font-size: .8rem; color: #6a737b; }
        .curso-nombre { font-weight: 700; color: #1f5f99; text-decoration: none; line-height: 1.3; }
        .min-w-0 { min-width: 0; }

        /* Bloque calendario. */
        .calendario .dia { width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; }
        .calendario .dia.hoy, .dia.hoy { outline: 2px solid var(--naranja); outline-offset: -2px; font-weight: 700; }
        .dia.con-clase { background: var(--unexo); color: #fff; border-radius: 50%; }
        .curso-nombre:hover { text-decoration: underline; }

        /* Cifras al estilo de moodle.org: icono naranja de linea + numero serif. */
        .cifra i { color: var(--naranja); font-size: 1.9rem; line-height: 1; }
        .cifra .numero { font-family: 'DM Serif Display', Georgia, serif; font-size: 2rem; color: var(--unexo); line-height: 1; }
        .cifra .etiqueta { color: #5b6670; font-size: .85rem; }

        .linea-tiempo { border-left: 3px solid var(--borde); margin-left: .5rem; }
        .linea-tiempo > li { position: relative; padding-left: 1.25rem; }
        .linea-tiempo > li::before {
            content: ''; position: absolute; left: -7px; top: .6rem;
            width: 11px; height: 11px; border-radius: 50%; background: var(--unexo);
        }
        .linea-tiempo > li.paso-aprobado::before { background: var(--bs-success); }
        .linea-tiempo > li.paso-actual::before { background: var(--naranja); box-shadow: 0 0 0 4px rgba(249, 128, 18, .25); }
        .linea-tiempo > li.paso-pendiente::before { background: #c8c0b6; }
        .linea-tiempo > li.paso-reprobado::before { background: var(--bs-danger); }
        .hero-unexo { background: var(--crema); }
    </style>
</head>
<body>


@auth
    @php
        $usuario = auth()->user();
        $esAdmin = $usuario->esAdministrador();
        $iniciales = mb_strtoupper(mb_substr($usuario->name, 0, 1).mb_substr($usuario->apellidos ?? '', 0, 1));
        $ruta = (string) request()->route()?->getName();
        $raiz = explode('.', $ruta)[0];

        // Administracion del sitio: como en Moodle 4, cada area es una pestana.
        $areasAdmin = [
            'general' => ['General', 'dashboard', ['dashboard']],
            'usuarios' => ['Usuarios', 'usuarios.index', ['usuarios', 'roles']],
            'catalogo' => ['Catalogo', 'programas.index', ['programas', 'modulos']],
            'planificacion' => ['Planificacion', 'cohortes.index', ['cohortes', 'grupos']],
            'matriculas' => ['Matriculas', 'matriculas.index', ['matriculas']],
        ];
        $subpaginas = [
            'usuarios' => [['Usuarios', route('usuarios.index'), 'usuarios.*'], ['Roles', route('roles.index'), 'roles.*']],
            'catalogo' => [['Programas', route('programas.index'), 'programas.*'], ['Modulos', route('modulos.index'), 'modulos.*']],
            'planificacion' => [
                ['Cohortes', route('cohortes.index'), 'cohortes.*', fn () => request('vista') !== 'historial'],
                ['Historial de cohortes', route('cohortes.index', ['vista' => 'historial']), 'cohortes.index', fn () => request('vista') === 'historial'],
                ['Grupos', route('grupos.index'), 'grupos.*'],
            ],
            'matriculas' => [['Matriculas', route('matriculas.index'), 'matriculas.index'], ['Inscribir alumno', route('matriculas.create'), 'matriculas.create']],
        ];

        $areaActual = null;
        if ($esAdmin) {
            foreach ($areasAdmin as $clave => [, , $raices]) {
                if (in_array($raiz, $raices, true)) {
                    $areaActual = $clave;
                }
            }
        }
    @endphp

    <nav class="navbar barra-superior fixed-top px-2 px-lg-3">
        <div class="d-flex align-items-center gap-2 w-100">
            {{-- Movil: abre el cajon como offcanvas. Escritorio: lo pliega. --}}
            <button class="btn-icono d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#cajon" aria-label="Abrir menu">
                <i class="bi bi-list"></i>
            </button>
            <button class="btn-icono d-none d-lg-inline-flex align-items-center justify-content-center" type="button" id="alternar-cajon" aria-label="Plegar menu">
                <i class="bi bi-list"></i>
            </button>

            <a class="logo-unexo me-3" href="{{ route('inicio') }}">
                <i class="bi bi-mortarboard-fill birrete"></i><span class="marca">unexo</span>
            </a>

            <ul class="navbar-nav flex-row nav-primaria d-none d-md-flex me-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('inicio') ? 'active' : '' }}" href="{{ route('inicio') }}">Pagina principal</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') && ! $esAdmin ? 'active' : '' }}" href="{{ route('dashboard') }}">Area personal</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('mis-cursos.*') ? 'active' : '' }}" href="{{ route('mis-cursos.index') }}">Mis cursos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('oferta.*') ? 'active' : '' }}" href="{{ route('oferta.index') }}">Oferta</a>
                </li>
                @if ($esAdmin)
                    <li class="nav-item">
                        <a class="nav-link {{ $areaActual ? 'active' : '' }}" href="{{ route('dashboard') }}">Administracion del sitio</a>
                    </li>
                @endif
            </ul>

            <div class="dropdown ms-auto">
                <a href="#" class="d-flex align-items-center gap-2 text-decoration-none text-body" data-bs-toggle="dropdown">
                    <span class="avatar">{{ $iniciales }}</span>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li class="px-3 py-2">
                        <div class="fw-semibold">{{ $usuario->nombre_completo }}</div>
                        <div class="small text-secondary">
                            {{ $usuario->roles->map(fn ($r) => ucfirst($r->nombre))->join(' · ') ?: 'sin rol' }}
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('perfil.show') }}"><i class="bi bi-person me-2"></i>Perfil</a></li>
                    <li><a class="dropdown-item" href="{{ route('password.cambio') }}"><i class="bi bi-key me-2"></i>Cambiar contrasena</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        {{-- Cerrar sesion cambia estado: va por POST, no por enlace. --}}
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesion</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <aside class="cajon offcanvas-lg offcanvas-start" id="cajon" tabindex="-1">
        <div class="offcanvas-header d-lg-none border-bottom">
            <span class="fw-bold">Menu</span>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#cajon"></button>
        </div>
        <nav class="py-2 w-100">
            <a class="enlace-cajon {{ request()->routeIs('dashboard') && ! $esAdmin ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i>Area personal</a>
            <a class="enlace-cajon {{ request()->routeIs('inicio') ? 'active' : '' }}" href="{{ route('inicio') }}"><i class="bi bi-globe2"></i>Pagina principal</a>
            <a class="enlace-cajon {{ request()->routeIs('mis-cursos.*') ? 'active' : '' }}" href="{{ route('mis-cursos.index') }}"><i class="bi bi-journal-bookmark"></i>Mis cursos</a>
            <a class="enlace-cajon {{ request()->routeIs('oferta.*') ? 'active' : '' }}" href="{{ route('oferta.index') }}"><i class="bi bi-mortarboard"></i>Oferta academica</a>

            @if ($misCursos->isNotEmpty())
                <div class="titulo-seccion">Cursos en marcha</div>
                @foreach ($misCursos as $curso)
                    <a class="enlace-cajon {{ request()->routeIs('grupos.*') && request()->route('grupo')?->id === $curso->id ? 'active' : '' }}"
                       href="{{ route('grupos.show', $curso) }}">
                        <x-curso-imagen :semilla="$curso->modulo_id" :alto="14" :ancho="14" class="rounded-1 flex-shrink-0" />
                        <span class="text-truncate">{{ $curso->nombre_completo }}</span>
                    </a>
                @endforeach
            @endif

            {{-- Los enlaces de admin solo se pintan para el admin. El
                 middleware 'rol' vuelve a comprobarlo en el servidor. --}}
            @if ($esAdmin)
                <div class="titulo-seccion">Administracion del sitio</div>
                @foreach ($areasAdmin as $clave => [$etiqueta, $destino])
                    <a class="enlace-cajon {{ $areaActual === $clave ? 'active' : '' }}" href="{{ route($destino) }}">
                        <i class="bi {{ ['general' => 'bi-gear', 'usuarios' => 'bi-people', 'catalogo' => 'bi-journal-bookmark', 'planificacion' => 'bi-calendar3', 'matriculas' => 'bi-person-check'][$clave] }}"></i>{{ $etiqueta }}
                    </a>
                @endforeach
            @endif
        </nav>
    </aside>
@else
    {{-- Barra publica: marca a la izquierda, acceso al login a la derecha. --}}
    <nav class="navbar barra-superior fixed-top px-3">
        <a class="logo-unexo" href="{{ route('inicio') }}">
            <i class="bi bi-mortarboard-fill birrete"></i><span class="marca">unexo</span>
        </a>
        @unless (request()->routeIs('login'))
            <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm btn-pastilla">Iniciar sesion <i class="bi bi-arrow-right ms-1"></i></a>
        @endunless
    </nav>
@endauth

<div class="contenido @guest ms-0 @endguest">

    @auth
        @php
            // Migas de pan como las de Moodle, armadas a partir del nombre de la ruta.
            $secciones = [
                'usuarios' => ['Usuarios', 'Usuarios'],
                'roles' => ['Usuarios', 'Roles'],
                'programas' => ['Catalogo', 'Programas'],
                'modulos' => ['Catalogo', 'Modulos'],
                'cohortes' => ['Planificacion', 'Cohortes'],
                'grupos' => ['Planificacion', 'Grupos'],
                'matriculas' => ['Matriculas', null],
                'oferta' => ['Oferta academica', null],
                'mis-cursos' => ['Mis cursos', null],
                'perfil' => ['Perfil', null],
                'password' => ['Cambiar contrasena', null],
            ];
            $acciones = ['create' => 'Nuevo', 'edit' => 'Editar', 'show' => 'Detalle', 'estructura' => 'Estructura', 'notas' => 'Notas', 'asignar' => 'Asignar docentes y Meet'];

            // Docentes y alumnos llegan a un grupo desde "Mis cursos", no desde la planificacion.
            if (! $esAdmin) {
                $secciones['grupos'] = ['Mis cursos', null];
                $secciones['mis-cursos'] = ['Mis cursos', null];
            }

            $partes = explode('.', $ruta);
            $migas = [];

            if (isset($secciones[$partes[0]])) {
                [$categoria, $pagina] = $secciones[$partes[0]];
                $accion = $acciones[$partes[1] ?? ''] ?? null;
                // Los listados de grupos, matriculas, etc. son solo del admin.
                $indice = Route::has($partes[0].'.index') && ($partes[0] === 'oferta' || $esAdmin)
                    ? route($partes[0].'.index')
                    : null;
                // Para docentes y alumnos, "Mis cursos" del detalle de un grupo lleva a su pagina.
                if (! $esAdmin && $partes[0] === 'grupos') {
                    $indice = route('mis-cursos.index');
                }

                if ($pagina) {
                    $migas[] = [$categoria, null];
                    $migas[] = [$pagina, $accion ? $indice : null];
                } else {
                    $migas[] = [$categoria, $accion ? $indice : null];
                }

                if ($accion) {
                    $migas[] = [$accion, null];
                }
            }

            $inicioMigas = $areaActual ? ['Administracion del sitio', route('dashboard')] : ['Area personal', route('dashboard')];
        @endphp

        @if ($areaActual)
            {{-- Cabecera de Moodle 4: migas, titulo y navegacion secundaria del area. --}}
            <header class="cabecera-pagina">
                <div class="interior">
                    @if ($migas)
                        <nav aria-label="Ruta">
                            <ol class="breadcrumb migas mb-0">
                                <li class="breadcrumb-item"><a href="{{ $inicioMigas[1] }}">{{ $inicioMigas[0] }}</a></li>
                                @foreach ($migas as [$texto, $enlace])
                                    <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}">
                                        @if ($enlace && ! $loop->last)
                                            <a href="{{ $enlace }}">{{ $texto }}</a>
                                        @else
                                            {{ $texto }}
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        </nav>
                    @endif
                    <h1>Administracion del sitio</h1>
                    <ul class="nav nav-secundaria">
                        @foreach ($areasAdmin as $clave => [$etiqueta, $destino])
                            <li class="nav-item">
                                <a class="nav-link {{ $areaActual === $clave ? 'active' : '' }}" href="{{ route($destino) }}">{{ $etiqueta }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </header>
        @endif
    @endauth

    <main class="contenido-interior">

        @auth
            @if ($areaActual && isset($subpaginas[$areaActual]))
                {{-- Navegacion terciaria: las paginas del area. --}}
                <nav class="nav nav-terciaria">
                    @foreach ($subpaginas[$areaActual] as $sub)
                        @php($activa = request()->routeIs($sub[2]) && (! isset($sub[3]) || $sub[3]()))
                        <a href="{{ $sub[1] }}" class="{{ $activa ? 'active' : '' }}">{{ $sub[0] }}</a>
                    @endforeach
                </nav>
            @elseif (! $areaActual && $migas)
                <nav aria-label="Ruta" class="mb-3">
                    <ol class="breadcrumb migas mb-0">
                        <li class="breadcrumb-item"><a href="{{ $inicioMigas[1] }}">{{ $inicioMigas[0] }}</a></li>
                        @foreach ($migas as [$texto, $enlace])
                            <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}">
                                @if ($enlace && ! $loop->last)
                                    <a href="{{ $enlace }}">{{ $texto }}</a>
                                @else
                                    {{ $texto }}
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @endif
        @endauth

        @if (session('exito'))
            <div class="alert alert-success alert-dismissible" role="alert">
                {{ session('exito') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('aviso'))
            <div class="alert alert-warning" role="alert">{{ session('aviso') }}</div>
        @endif

        @yield('content')
    </main>

    <footer class="pie py-4 px-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3" style="max-width: 1320px; margin: 0 auto;">
            <span class="logo-unexo"><i class="bi bi-mortarboard-fill birrete"></i><span class="marca">unexo</span></span>
            <span>Formacion continua &middot; SIS UNEXO</span>
            @auth
                <span>Ha iniciado sesion como <strong>{{ auth()->user()->nombre_completo }}</strong></span>
            @endauth
        </div>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script>
    // Recuerda si el cajon lateral esta plegado (solo comodidad local).
    (function () {
        const boton = document.getElementById('alternar-cajon');
        try { if (localStorage.getItem('cajon-cerrado') === '1') document.body.classList.add('cajon-cerrado'); } catch (e) {}
        if (!boton) return;
        boton.addEventListener('click', function () {
            const cerrado = document.body.classList.toggle('cajon-cerrado');
            try { localStorage.setItem('cajon-cerrado', cerrado ? '1' : '0'); } catch (e) {}
        });
    })();
</script>
@stack('scripts')
</body>
</html>
