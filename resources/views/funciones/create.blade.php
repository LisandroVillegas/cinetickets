@extends('layouts.app')

@section('titulo', 'Nueva función')

@section('contenido')
    <h1 class="h3 mb-3">Nueva función</h1>

    <form action="{{ route('funciones.store') }}" method="POST" class="card card-body">
        @csrf
        @include('funciones._form')

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="{{ route('funciones.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
@endsection
