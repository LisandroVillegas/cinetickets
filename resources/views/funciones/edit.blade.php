@extends('layouts.app')

@section('titulo', 'Editar función')

@section('contenido')
    <h1 class="h3 mb-3">Editar función #{{ $funcion->id_funcion }}</h1>

    <form action="{{ route('funciones.update', $funcion) }}" method="POST" class="card card-body">
        @csrf
        @method('PUT')
        @include('funciones._form')

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="{{ route('funciones.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
@endsection
