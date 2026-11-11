@extends('layouts.app')

@section('titulo', 'Editar datos del boleto')

@section('contenido')
    <div class="mb-3">
        <h1 class="h3">Editar datos del cliente</h1>
        <p class="text-muted mb-0">Boleto #{{ $boleto->id_boleto }}</p>
    </div>

    <div class="card bg-white mb-3">
        <div class="card-header">Datos de la compra</div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Función</dt>
                <dd class="col-sm-9">
                    {{ $boleto->funcion->titulo }} — {{ $boleto->funcion->fecha_hora->format('d/m/Y H:i') }}
                </dd>

                <dt class="col-sm-3">Cantidad</dt>
                <dd class="col-sm-9">{{ $boleto->cantidad_entradas }} entrada(s)</dd>

                <dt class="col-sm-3">Monto total</dt>
                <dd class="col-sm-9">${{ number_format((float) $boleto->monto_total, 2, ',', '.') }}</dd>

                <dt class="col-sm-3">Estado</dt>
                <dd class="col-sm-9">{{ $boleto->estado }}</dd>
            </dl>
        </div>
    </div>

    <form action="{{ route('boletos.update', $boleto) }}" method="POST" class="card card-body bg-white">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="nombre_cliente" class="form-label">Nombre del cliente</label>
            <input
                type="text"
                name="nombre_cliente"
                id="nombre_cliente"
                class="form-control @error('nombre_cliente') is-invalid @enderror"
                value="{{ old('nombre_cliente', $boleto->nombre_cliente) }}"
                maxlength="100"
                required
            >
            @error('nombre_cliente')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="email_cliente" class="form-label">Correo electrónico</label>
            <input
                type="email"
                name="email_cliente"
                id="email_cliente"
                class="form-control @error('email_cliente') is-invalid @enderror"
                value="{{ old('email_cliente', $boleto->email_cliente) }}"
                maxlength="100"
                required
            >
            @error('email_cliente')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="{{ route('boletos.index') }}" class="btn btn-outline-secondary">Volver a boletos</a>
        </div>
    </form>
@endsection
