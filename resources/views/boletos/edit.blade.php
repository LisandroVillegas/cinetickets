@extends('layouts.app')

@section('titulo', 'Editar datos del boleto')

@php($camposFormulario = ['nombre_cliente', 'email_cliente'])

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
                <dd class="col-sm-9">COP ${{ number_format((float) $boleto->monto_total, 2, ',', '.') }}</dd>

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
                aria-describedby="nombre_cliente-error" @error('nombre_cliente') aria-invalid="true" @enderror
                class="form-control @error('nombre_cliente') is-invalid @enderror"
                value="{{ old('nombre_cliente', $boleto->nombre_cliente) }}"
                maxlength="100"
                required
            >
            <div id="nombre_cliente-error" class="invalid-feedback">@error('nombre_cliente') {{ $message }} @enderror</div>
        </div>

        <div class="mb-3">
            <label for="email_cliente" class="form-label">Correo electrónico</label>
            <input
                type="email"
                name="email_cliente"
                id="email_cliente"
                aria-describedby="email_cliente-error" @error('email_cliente') aria-invalid="true" @enderror
                class="form-control @error('email_cliente') is-invalid @enderror"
                value="{{ old('email_cliente', $boleto->email_cliente) }}"
                maxlength="100"
                required
            >
            <div id="email_cliente-error" class="invalid-feedback">@error('email_cliente') {{ $message }} @enderror</div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="{{ route('boletos.index') }}" class="btn btn-outline-secondary">Volver a boletos</a>
        </div>
    </form>
@endsection
