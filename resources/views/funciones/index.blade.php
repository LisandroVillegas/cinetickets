@extends('layouts.app')

@section('titulo', 'Cartelera')

@section('contenido')
    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Cartelera de funciones</h1>

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('ventas.create') }}" class="btn btn-success">
            Vender boletos
        </a>

        <a href="{{ route('funciones.create') }}" class="btn btn-primary">
            Nueva función
        </a>
    </div>
</div>


    

    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle bg-white">
            <caption class="visually-hidden">Funciones, disponibilidad y acciones</caption>
            <thead class="table-dark">
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Título</th>
                    <th scope="col">Género</th>
                    <th scope="col">Duración</th>
                    <th scope="col">Sala</th>
                    <th scope="col">Fecha y hora</th>
                    <th scope="col">Precio</th>
                    <th scope="col">Capacidad</th>
                    <th scope="col">Disponibles</th>
                    <th scope="col">Boletos</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($funciones as $f)
                    <tr>
                        <td>{{ $f->id_funcion }}</td>
                        <td>{{ $f->titulo }}</td>
                        <td>{{ $f->genero }}</td>
                        <td>{{ $f->duracion_min }} min</td>
                        <td>{{ $f->sala }}</td>
                        <td>{{ $f->fecha_hora->format('d/m/Y H:i') }}</td>
                        <td>COP {{ number_format($f->precio_unitario, 2, ',', '.') }}</td>
                        <td>{{ $f->capacidad_total }}</td>
                        <td>{{ $f->asientos_disponibles }}</td>
                        <td>{{ $f->boletos_count }}</td>
                        <td>
                            <span class="badge {{ $f->estado === 'Activa' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $f->estado }}
                            </span>
                        </td>
                        <td class="acciones">
                            <a href="{{ route('funciones.edit', $f) }}" class="btn btn-sm btn-outline-primary">Editar</a>

                            @if ($f->estado === 'Activa')
                                <form action="{{ route('funciones.desactivar', $f) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-warning">Inactivar</button>
                                </form>
                            @endif

                            @if ($f->boletos_count === 0)
                            <form action="{{ route('funciones.destroy', $f) }}" method="POST" class="d-inline"
                                  data-confirmar="¿Eliminar la función #{{ $f->id_funcion }}?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
                            @else
                                <span class="d-block small text-muted mt-2">Con historial: no se puede eliminar.</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center text-muted">No hay funciones registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
