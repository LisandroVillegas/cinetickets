@extends('layouts.app')
@section('titulo', 'Vender boletos')
@php($camposFormulario = ['id_funcion', 'nombre_cliente', 'email_cliente', 'cantidad_entradas'])
@section('contenido')
    <h1 class="h3 mb-2">Vender boletos</h1>
    <p class="text-muted">Elige una función y revisa los datos antes de confirmar la compra.</p>
    @if ($funciones->isEmpty())
        <div class="alert alert-warning">No hay funciones activas con asientos disponibles. Vuelve a consultar la cartelera para elegir otra función.</div>
        <a href="{{ route('funciones.index') }}" class="btn btn-outline-secondary">Ver funciones</a>
    @endif
        <form action="{{ route('ventas.store') }}" method="POST" class="card card-body" data-venta>
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="id_funcion">Función</label>
                    <select class="form-select @error('id_funcion') is-invalid @enderror" id="id_funcion" name="id_funcion" required @disabled($funciones->isEmpty())
                            aria-describedby="id_funcion-ayuda id_funcion-error" @error('id_funcion') aria-invalid="true" @enderror>
                        <option value="">{{ old('id_funcion') && !$funciones->contains('id_funcion', old('id_funcion')) ? 'La selección anterior ya no está disponible; elige otra función' : 'Seleccione una función' }}</option>
                        @foreach ($funciones as $f)
                            <option value="{{ $f->id_funcion }}" data-precio="{{ $f->precio_unitario }}" data-cupos="{{ $f->asientos_disponibles }}"
                                    @selected((string) old('id_funcion') === (string) $f->id_funcion)>
                                {{ $f->titulo }} · {{ $f->sala }} · {{ $f->fecha_hora->format('d/m/Y H:i') }} · COP {{ number_format($f->precio_unitario, 2, ',', '.') }} · {{ $f->asientos_disponibles }} disponibles
                            </option>
                        @endforeach
                    </select>
                    <p id="id_funcion-ayuda" class="form-text mb-0">Horarios de Colombia. Los cupos se comprueban de nuevo al confirmar.</p>
                    <div id="id_funcion-error" class="invalid-feedback">@error('id_funcion') {{ $message }} @enderror</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="nombre_cliente">Nombre del cliente</label>
                    <input type="text" class="form-control @error('nombre_cliente') is-invalid @enderror" id="nombre_cliente" name="nombre_cliente" autocomplete="name" required maxlength="100"
                           value="{{ old('nombre_cliente') }}" aria-describedby="nombre_cliente-error" @error('nombre_cliente') aria-invalid="true" @enderror>
                    <div id="nombre_cliente-error" class="invalid-feedback">@error('nombre_cliente') {{ $message }} @enderror</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email_cliente">Correo electrónico</label>
                    <input type="email" class="form-control @error('email_cliente') is-invalid @enderror" id="email_cliente" name="email_cliente" autocomplete="email" required maxlength="100"
                           value="{{ old('email_cliente') }}" aria-describedby="email_cliente-error" @error('email_cliente') aria-invalid="true" @enderror>
                    <div id="email_cliente-error" class="invalid-feedback">@error('email_cliente') {{ $message }} @enderror</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="cantidad_entradas">Cantidad de entradas</label>
                    <input type="number" min="1" step="1" class="form-control @error('cantidad_entradas') is-invalid @enderror" id="cantidad_entradas" name="cantidad_entradas" required
                           value="{{ old('cantidad_entradas') }}" aria-describedby="cantidad_entradas-error" @error('cantidad_entradas') aria-invalid="true" @enderror>
                    <div id="cantidad_entradas-error" class="invalid-feedback">@error('cantidad_entradas') {{ $message }} @enderror</div>
                </div>
            </div>
            <section class="mt-4 border-top pt-3" aria-labelledby="resumen-titulo">
                <h2 class="h5" id="resumen-titulo">Revisar compra</h2>
                <div data-resumen aria-live="polite" aria-atomic="true" class="mb-3">Revisa la función, la cantidad, el nombre y el correo antes de enviar.</div>
                <p class="mb-1">Total estimado: <strong data-total>Se calcula con la cantidad indicada</strong></p>
                <p class="text-muted small">El servidor calcula el importe final en COP y confirma la disponibilidad.</p>
                <noscript><p>El precio por entrada aparece en la función. Revisa los campos; el importe final se mostrará después de guardar la venta.</p></noscript>
            </section>
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary" @disabled($funciones->isEmpty())>Confirmar compra</button>
                <a href="{{ route('funciones.index') }}" class="btn btn-outline-secondary">Volver a funciones</a>
            </div>
            <p class="small text-muted mt-3 mb-0" data-envio role="status">Si se interrumpe la conexión al confirmar, consulta los boletos antes de volver a enviar la compra.</p>
        </form>
@endsection
