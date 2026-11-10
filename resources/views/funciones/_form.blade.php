{{-- Campos compartidos por crear y editar. En "editar" llega la variable $funcion. --}}
@php($f = $funcion ?? null)

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="titulo">Título</label>
        <input type="text" class="form-control" id="titulo" name="titulo"
               value="{{ old('titulo', $f?->titulo) }}">
    </div>

    <div class="col-md-6">
        <label class="form-label" for="genero">Género</label>
        <input type="text" class="form-control" id="genero" name="genero"
               value="{{ old('genero', $f?->genero) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label" for="duracion_min">Duración (minutos)</label>
        <input type="number" class="form-control" id="duracion_min" name="duracion_min"
               value="{{ old('duracion_min', $f?->duracion_min) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label" for="sala">Sala</label>
        <input type="text" class="form-control" id="sala" name="sala"
               value="{{ old('sala', $f?->sala) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label" for="fecha_hora">Fecha y hora</label>
        <input type="datetime-local" class="form-control" id="fecha_hora" name="fecha_hora"
               value="{{ old('fecha_hora', $f?->fecha_hora?->format('Y-m-d\TH:i')) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label" for="precio_unitario">Precio unitario</label>
        <input type="number" step="0.01" class="form-control" id="precio_unitario" name="precio_unitario"
               value="{{ old('precio_unitario', $f?->precio_unitario) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label" for="capacidad_total">Capacidad total</label>
        <input type="number" class="form-control" id="capacidad_total" name="capacidad_total"
               value="{{ old('capacidad_total', $f?->capacidad_total) }}">
    </div>

    @isset($funcion)
        <div class="col-md-4">
            <label class="form-label" for="estado">Estado</label>
            <select class="form-select" id="estado" name="estado">
                @foreach (['Activa', 'Inactiva'] as $opcion)
                    <option value="{{ $opcion }}" @selected(old('estado', $funcion->estado) === $opcion)>
                        {{ $opcion }}
                    </option>
                @endforeach
            </select>
        </div>
    @endisset
</div>
