@extends('layouts.app')

@section('titulo', 'Boletos vendidos')

@section('contenido')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Boletos vendidos</h1>
        <a href="{{ route('ventas.create') }}" class="btn btn-primary">Registrar venta</a>
    </div>

    <form action="{{ route('boletos.index') }}" method="GET" class="card card-body bg-white mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="cliente" class="form-label">Cliente</label>
                <input
                    type="search"
                    name="cliente"
                    id="cliente"
                    class="form-control"
                    value="{{ $filtros['cliente'] ?? '' }}"
                    placeholder="Nombre o correo"
                >
            </div>

            <div class="col-md-5">
                <label for="funcion" class="form-label">Función</label>
                <select name="funcion" id="funcion" class="form-select">
                    <option value="">Todas las funciones</option>
                    @foreach ($funciones as $funcion)
                        <option value="{{ $funcion->id_funcion }}"
                            @selected((string) ($filtros['funcion'] ?? '') === (string) $funcion->id_funcion)>
                            {{ $funcion->titulo }} — {{ $funcion->fecha_hora->format('d/m/Y H:i') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark">Buscar</button>
                <a href="{{ route('boletos.index') }}" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle bg-white">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Correo</th>
                    <th>Función</th>
                    <th>Cantidad</th>
                    <th>Monto total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($boletos as $boleto)
                    <tr>
                        <td>{{ $boleto->id_boleto }}</td>
                        <td>{{ $boleto->nombre_cliente }}</td>
                        <td>{{ $boleto->email_cliente }}</td>
                        <td>
                            {{ $boleto->funcion->titulo }}<br>
                            <small class="text-muted">
                                {{ $boleto->funcion->fecha_hora->format('d/m/Y H:i') }} · Sala {{ $boleto->funcion->sala }}
                            </small>
                        </td>
                        <td>{{ $boleto->cantidad_entradas }}</td>
                        <td>${{ number_format((float) $boleto->monto_total, 2, ',', '.') }}</td>
                        <td>
                            <span class="badge {{ $boleto->estado === 'Confirmado' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $boleto->estado }}
                            </span>
                        </td>
                        <td class="text-nowrap">
                            @if ($boleto->estado === 'Confirmado')
                                <a href="{{ route('boletos.edit', $boleto) }}" class="btn btn-sm btn-outline-primary">
                                    Editar cliente
                                </a>
                            @else
                                <span class="text-muted">No editable</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No se encontraron boletos.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted">
            Mostrando {{ $boletos->firstItem() ?? 0 }}–{{ $boletos->lastItem() ?? 0 }} de {{ $boletos->total() }} boletos
        </small>
        @if ($boletos->hasPages())
            <nav aria-label="Paginación de boletos">
                <ul class="pagination mb-0">
                    <li class="page-item {{ $boletos->onFirstPage() ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $boletos->previousPageUrl() ?? '#' }}" aria-label="Anterior">Anterior</a>
                    </li>
                    <li class="page-item disabled">
                        <span class="page-link">Página {{ $boletos->currentPage() }} de {{ $boletos->lastPage() }}</span>
                    </li>
                    <li class="page-item {{ $boletos->hasMorePages() ? '' : 'disabled' }}">
                        <a class="page-link" href="{{ $boletos->nextPageUrl() ?? '#' }}" aria-label="Siguiente">Siguiente</a>
                    </li>
                </ul>
            </nav>
        @endif
    </div>
@endsection
