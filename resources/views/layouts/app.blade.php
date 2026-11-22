<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'CineTickets') · CineTickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light">
<a class="visually-hidden-focusable skip-link" href="#contenido">Saltar al contenido</a>
<nav class="navbar navbar-dark bg-dark mb-4" aria-label="Navegación principal">
    <div class="container gap-3">
        <a class="navbar-brand" href="{{ route('funciones.index') }}">CineTickets</a>
        <div class="navbar-nav flex-row flex-wrap gap-3">
            @foreach (['funciones' => ['funciones.index', 'Funciones'], 'ventas' => ['ventas.create', 'Vender'], 'boletos' => ['boletos.index', 'Boletos']] as $seccion => [$ruta, $texto])
                <a class="nav-link {{ request()->routeIs($seccion.'.*') ? 'active' : '' }}" href="{{ route($ruta) }}"
                   @if(request()->routeIs($seccion.'.*')) aria-current="page" @endif>{{ $texto }}</a>
            @endforeach
        </div>
    </div>
</nav>
<main id="contenido" class="container pb-5" tabindex="-1">
    @if (session('success') ?? session('exito'))
        <div class="alert alert-success" role="status">{{ session('success') ?? session('exito') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert" tabindex="-1" id="resumen-errores">
            <h2 class="h6">Revisa los datos antes de continuar</h2>
            <ul class="mb-0">
                @foreach ($errors->messages() as $campo => $mensajes)
                    @foreach ($mensajes as $mensaje)
                        <li>@if(in_array($campo, $camposFormulario ?? [], true))<a href="#{{ $campo }}">{{ $mensaje }}</a>@else{{ $mensaje }}@endif</li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    @endif
    @yield('contenido')
</main>
</body>
</html>
