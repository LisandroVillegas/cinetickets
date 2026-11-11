<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'CineTickets')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="{{ route('funciones.index') }}">CineTickets</a>
        <div class="navbar-nav">
            <a class="nav-link" href="{{ route('funciones.index') }}">Cartelera</a>
            <a class="nav-link" href="{{ route('boletos.index') }}">Boletos vendidos</a>
            <a class="nav-link" href="{{ route('ventas.create') }}">Registrar venta</a>
        </div>
    </div>
</nav>

<main class="container pb-5">
    @if (session('exito'))
        <div class="alert alert-success">{{ session('exito') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $mensaje)
                    <li>{{ $mensaje }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('contenido')
</main>
</body>
</html>
