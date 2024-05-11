@extends('system.layouts.app')

@section('content')

@if($error)
    <form action="{{ route('errors.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('POST')

    <div class="row">
        <div class="col-md-12">
            <label for="image_file">Subir imagen:</label>
            <input type="file" id="image_file" name="image_file" class="form-control">
        </div>
    </div>
    <div class="row">
    <div class="col-md-12 text-center">
        @if($error->img)
            <img src="{{ asset($error->img) }}" class="img-fluid" alt="Imagen actual">
        @else
            <p>No hay imagen actual.</p>
        @endif
    </div>
</div>


    <div class="row">
        <div class="col-md-4">
            <label for="text1">Título:</label>
            <input type="text" id="text1" name="text1" class="form-control" value="{{ $error->titulo }}">
        </div>
        <div class="col-md-4">
            <label for="text2">Comentario:</label>
            <input type="text" id="text2" name="text2" class="form-control" value="{{ $error->comentario2 }}">
        </div>
        <div class="col-md-4">
            <label for="text3">Mensaje:</label>
            <input type="text" id="text3" name="text3" class="form-control" value="{{ $error->adm }}">
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary">Actualizar</button>
        </div>
    </div>
    </form>
@else
    <div class="row">
        <div class="col-md-12">
            <p>No se encontraron errores.</p>
        </div>
    </div>
@endif

@endsection
