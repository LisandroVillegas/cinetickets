{{-- Incluir desde el listado con: @include('boletos._anular', ['boleto' => $boleto]) --}}
@if ($boleto->estado === 'Confirmado' && $boleto->funcion->fecha_hora->isFuture())
    <form action="{{ route('boletos.anular', $boleto) }}" method="POST" class="d-inline"
          onsubmit="return confirm('¿Anular este boleto y devolver sus asientos?')">
        @csrf
        @method('PATCH')
        <button type="submit" class="btn btn-sm btn-outline-danger">Anular boleto</button>
    </form>
@endif
