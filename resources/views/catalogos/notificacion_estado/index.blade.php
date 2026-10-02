@extends('layouts.app')

@section('content')
    @include('catalogos.estado_simple.index', [
        'tipo' => 'notificaciones',
        'singular' => 'notificación',
        'descripcion' => 'Gestiona los estados disponibles para el envío de notificaciones por correo.',
        'ruta' => 'catalogo-estados-notificaciones',
        'modal' => 'notificacion-estado',
        'tieneDescripcion' => false,
        'mensajeUso' => 'Solo se eliminará lógicamente si no está utilizado por una notificación.',
    ])
@endsection
