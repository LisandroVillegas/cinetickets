{{-- El servidor copia los datos del catálogo; estos controles no determinan aforo ni disponibilidad. --}}
@php($f = $funcion ?? null)
@if ($peliculas->isEmpty() || $salas->isEmpty())
    <div class="alert alert-warning">No hay películas o salas registradas. Solicita el registro de ambos catálogos antes de guardar una función.</div>
@endif
<div class="row g-3" data-programacion>
    <div class="col-md-6">
        <label class="form-label" for="pelicula_id">Película</label>
        <select class="form-select @error('pelicula_id') is-invalid @enderror" id="pelicula_id" name="pelicula_id" required
                aria-describedby="pelicula_id-ayuda pelicula_id-error" @error('pelicula_id') aria-invalid="true" @enderror>
            <option value="">Seleccione una película</option>
            @foreach ($peliculas as $pelicula)
                <option value="{{ $pelicula->id }}" data-duracion="{{ $pelicula->duracion_min }}" @selected((string) old('pelicula_id', $f?->pelicula_id) === (string) $pelicula->id)>
                    {{ $pelicula->titulo }} ({{ $pelicula->duracion_min }} min){{ $pelicula->activa ? '' : ' — Inactiva' }}
                </option>
            @endforeach
        </select>
        <p id="pelicula_id-ayuda" class="form-text mb-0">La duración se toma del catálogo. <span data-duracion aria-live="polite"></span></p>
        <div id="pelicula_id-error" class="invalid-feedback">@error('pelicula_id') {{ $message }} @enderror</div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="sala_id">Sala</label>
        <select class="form-select @error('sala_id') is-invalid @enderror" id="sala_id" name="sala_id" required
                aria-describedby="sala_id-ayuda sala_id-error" @error('sala_id') aria-invalid="true" @enderror>
            <option value="">Seleccione una sala</option>
            @foreach ($salas as $sala)
                <option value="{{ $sala->id }}" data-capacidad="{{ $sala->capacidad }}" @selected((string) old('sala_id', $f?->sala_id) === (string) $sala->id)>
                    {{ $sala->nombre }} (Capacidad: {{ $sala->capacidad }}){{ $sala->activa ? '' : ' — Inactiva' }}
                </option>
            @endforeach
        </select>
        <p id="sala_id-ayuda" class="form-text mb-0">La capacidad se toma de la sala. <span data-capacidad aria-live="polite"></span></p>
        <div id="sala_id-error" class="invalid-feedback">@error('sala_id') {{ $message }} @enderror</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="fecha_hora">Fecha y hora</label>
        <input type="datetime-local" class="form-control @error('fecha_hora') is-invalid @enderror" id="fecha_hora" name="fecha_hora"
               value="{{ old('fecha_hora', $f?->fecha_hora?->format('Y-m-d\TH:i')) }}" required
               aria-describedby="fecha_hora-ayuda fecha_hora-error" @error('fecha_hora') aria-invalid="true" @enderror>
        <p id="fecha_hora-ayuda" class="form-text mb-0">Hora de Colombia (America/Bogota).</p>
        <div id="fecha_hora-error" class="invalid-feedback">@error('fecha_hora') {{ $message }} @enderror</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="precio_unitario">Precio unitario (COP)</label>
        <input type="number" min="0.01" max="99999999.99" step="0.01" class="form-control @error('precio_unitario') is-invalid @enderror" id="precio_unitario" name="precio_unitario"
               value="{{ old('precio_unitario', $f?->precio_unitario) }}" required aria-describedby="precio_unitario-error" @error('precio_unitario') aria-invalid="true" @enderror>
        <div id="precio_unitario-error" class="invalid-feedback">@error('precio_unitario') {{ $message }} @enderror</div>
    </div>
    @isset($funcion)
        <div class="col-md-4">
            <label class="form-label" for="estado">Estado</label>
            <select class="form-select @error('estado') is-invalid @enderror" id="estado" name="estado" required aria-describedby="estado-error" @error('estado') aria-invalid="true" @enderror>
                @foreach (['Activa', 'Inactiva'] as $opcion)
                    <option value="{{ $opcion }}" @selected(old('estado', $funcion->estado) === $opcion)>{{ $opcion }}</option>
                @endforeach
            </select>
            <div id="estado-error" class="invalid-feedback">@error('estado') {{ $message }} @enderror</div>
        </div>
        <div class="col-12">
            <h2 class="h6">Datos históricos de esta función</h2>
            <p class="text-muted mb-0">{{ $funcion->titulo }} · {{ $funcion->duracion_min }} min · {{ $funcion->sala }} · Capacidad: {{ $funcion->capacidad_total }}. Los valores mostrados en los selectores corresponden al catálogo actual.</p>
        </div>
    @endisset
</div>
