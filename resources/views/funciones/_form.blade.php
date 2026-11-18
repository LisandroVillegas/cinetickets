{{-- Campos compartidos por crear y editar. En "editar" llega la variable $funcion. --}}
@php($f = $funcion ?? null)

<div class="col-md-6">
        <label class="form-label" for="pelicula_id">Película</label>
        <select class="form-select" id="pelicula_id" name="pelicula_id" required>
            <option value="">Seleccione una película...</option>
            @foreach ($peliculas as $pelicula)
                <option value="{{ $pelicula->id }}" @selected(old('pelicula_id', $f?->pelicula_id) == $pelicula->id)>
                    {{ $pelicula->titulo }} ({{ $pelicula->duracion_min }} min)
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="sala_id">Sala</label>
        <select class="form-select" id="sala_id" name="sala_id" required>
            <option value="">Seleccione una sala...</option>
            @foreach ($salas as $sala)
                <option value="{{ $sala->id }}" @selected(old('sala_id', $f?->sala_id) == $sala->id)>
                    {{ $sala->nombre }} (Capacidad: {{ $sala->capacidad }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="fecha_hora">Fecha y hora</label>
        <input type="datetime-local" class="form-control" id="fecha_hora" name="fecha_hora"
               value="{{ old('fecha_hora', $f?->fecha_hora?->format('Y-m-d\TH:i')) }}" required>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="precio_unitario">Precio unitario</label>
        <input type="number" step="0.01" class="form-control" id="precio_unitario" name="precio_unitario"
               value="{{ old('precio_unitario', $f?->precio_unitario) }}" required>
    </div>

    @isset($funcion)
        <div class="col-md-4">
            <label class="form-label" for="estado">Estado</label>
            <select class="form-select" id="estado" name="estado" required>
                @foreach (['Activa', 'Inactiva'] as $opcion)
                    <option value="{{ $opcion }}" @selected(old('estado', $funcion->estado) === $opcion)>
                        {{ $opcion }}
                    </option>
                @endforeach
            </select>
        </div>
    @endisset
</div>