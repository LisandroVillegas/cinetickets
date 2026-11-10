@extends('layouts.app')

@section('titulo', 'Cartelera')

@section('contenido')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Cartelera de funciones</h1>
        <a href="{{ route('funciones.create') }}" class="btn btn-primary">Nueva función</a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle bg-white">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Género</th>
                    <th>Duración</th>
                    <th>Sala</th>
                    <th>Fecha y hora</th>
                    <th>Precio</th>
                    <th>Capacidad</th>
                    <th>Disponibles</th>
                    <th>Boletos</th>
                    <th>Estado</th>
                    <th>Acciones</th>
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
                        <td>{{ number_format($f->precio_unitario, 2) }}</td>
                        <td>{{ $f->capacidad_total }}</td>
                        <td>{{ $f->asientos_disponibles }}</td>
                        <td>{{ $f->boletos_count }}</td>
                        <td>
                            <span class="badge {{ $f->estado === 'Activa' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $f->estado }}
                            </span>
                        </td>
                        <td class="text-nowrap">
                            <a href="{{ route('funciones.edit', $f) }}" class="btn btn-sm btn-outline-primary">Editar</a>

                            @if ($f->estado === 'Activa')
                                <form action="{{ route('funciones.desactivar', $f) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-warning">Desactivar</button>
                                </form>
                            @endif

                            <form action="{{ route('funciones.destroy', $f) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar esta función?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
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
