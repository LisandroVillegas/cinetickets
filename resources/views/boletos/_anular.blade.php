{{-- El motivo y la fecha de anulación requieren el backend de la siguiente etapa. --}}
@if ($boleto->estado === 'Confirmado' && $boleto->funcion->fecha_hora->isFuture())
    <details class="anulacion mt-2" id="anular-{{ $boleto->id_boleto }}">
        <summary>Revisar anulación <span class="visually-hidden">del boleto #{{ $boleto->id_boleto }}</span></summary>
        <form action="{{ route('boletos.anular', $boleto) }}" method="POST" class="mt-2" data-confirmar="¿Anular el boleto #{{ $boleto->id_boleto }} y liberar sus entradas?">
            @csrf
            @method('PATCH')
            <p class="small mb-2">Se anularán las {{ $boleto->cantidad_entradas }} entradas del boleto #{{ $boleto->id_boleto }} y se liberarán sus cupos. Esta acción no realiza una devolución bancaria.</p>
            <button type="submit" class="btn btn-sm btn-outline-danger">Anular boleto</button>
        </form>
    </details>
@endif
