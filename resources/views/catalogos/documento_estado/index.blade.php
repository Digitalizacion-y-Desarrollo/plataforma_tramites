@extends('layouts.app')

@section('content')
    @include('catalogos.estado_simple.index', [
        'tipo' => 'documentos',
        'singular' => 'documento',
        'descripcion' => 'Gestiona los estados disponibles para los documentos de las solicitudes.',
        'ruta' => 'catalogo-estados-documentos',
        'modal' => 'documento-estado',
        'tieneDescripcion' => true,
        'mensajeUso' => 'Solo se eliminará lógicamente si no está utilizado por un documento de solicitud.',
    ])
@endsection
