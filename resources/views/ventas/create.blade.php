@extends('layouts.app')

@section('titulo', 'Vender boletos')

@section('contenido')
    <h1 class="h3 mb-3">Vender boletos</h1>

    @if ($funciones->isEmpty())
        <div class="alert alert-warning">No hay funciones activas con asientos disponibles.</div>
    @else
        <form action="{{ route('ventas.store') }}" method="POST" class="card card-body">
            @csrf

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="id_funcion">Función</label>
                    <select class="form-select" id="id_funcion" name="id_funcion">
                        <option value="">Seleccione una función</option>
                        @foreach ($funciones as $f)
                            <option value="{{ $f->id_funcion }}"
                                    data-precio="{{ $f->precio_unitario }}"
                                    @selected((string) old('id_funcion') === (string) $f->id_funcion)>
                                {{ $f->titulo }} · {{ $f->sala }} · {{ $f->fecha_hora->format('d/m/Y H:i') }}
                                · ${{ number_format($f->precio_unitario, 2) }}
                                · {{ $f->asientos_disponibles }} disponibles
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="nombre_cliente">Nombre del cliente</label>
                    <input type="text" class="form-control" id="nombre_cliente" name="nombre_cliente"
                           value="{{ old('nombre_cliente') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="email_cliente">Correo electrónico</label>
                    <input type="text" class="form-control" id="email_cliente" name="email_cliente"
                           value="{{ old('email_cliente') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="cantidad_entradas">Cantidad de entradas</label>
                    <input type="number" class="form-control" id="cantidad_entradas" name="cantidad_entradas"
                           value="{{ old('cantidad_entradas') }}">
                </div>

                <div class="col-md-8 d-flex align-items-end">
                    <p class="mb-2 fs-5">Total estimado: $<span id="total">0,00</span></p>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Confirmar compra</button>
                <a href="{{ route('funciones.index') }}" class="btn btn-secondary">Volver a la cartelera</a>
            </div>
        </form>

        {{-- El total de la pantalla es solo una ayuda visual: el servidor lo vuelve a calcular. --}}
        <script>
            const funcion = document.getElementById('id_funcion');
            const cantidad = document.getElementById('cantidad_entradas');
            const total = document.getElementById('total');

            function actualizarTotal() {
                const opcion = funcion.options[funcion.selectedIndex];
                const precio = parseFloat(opcion.dataset.precio || 0);
                const entradas = parseInt(cantidad.value || 0, 10);
                total.textContent = (precio * entradas).toLocaleString('es-CO', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            funcion.addEventListener('change', actualizarTotal);
            cantidad.addEventListener('input', actualizarTotal);
            actualizarTotal();
        </script>
    @endif
@endsection
