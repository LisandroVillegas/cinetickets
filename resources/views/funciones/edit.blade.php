@extends('layouts.app')

@section('titulo', 'Editar función')

@php($camposFormulario = ['pelicula_id', 'sala_id', 'fecha_hora', 'precio_unitario', 'estado'])

@section('contenido')
    <h1 class="h3 mb-3">Editar función #{{ $funcion->id_funcion }}</h1>

    <form action="{{ route('funciones.update', $funcion) }}" method="POST" class="card card-body">
        @csrf
        @method('PUT')
        @include('funciones._form')

        <div class="mt-4 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary" @disabled($peliculas->isEmpty() || $salas->isEmpty())>Actualizar</button>
            <a href="{{ route('funciones.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
@endsection
