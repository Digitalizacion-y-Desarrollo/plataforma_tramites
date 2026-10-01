<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

// Rutas del Creador de Formularios

Route::resource('formulario', 'App\Http\Controllers\FormularioController');

Route::prefix('formulario/{formulario}')
    ->controller('App\Http\Controllers\FormularioController')
    ->group(function () {
        Route::get('duplicar', 'createDuplicado')->name('formulario.duplicar');
        Route::post('duplicar', 'storeDuplicado')->name('formulario.duplicar.store');
        Route::post('secciones', 'storeSeccion')->name('formulario.secciones.store');
        Route::delete('secciones/{seccion}', 'destroySeccion')->name('formulario.secciones.destroy');
        Route::post('campos/orden', 'ordenarCampos')->name('formulario.campos.ordenar');
        Route::post('secciones/{seccion}/campos', 'storeCampo')->name('formulario.secciones.campos.store');
        Route::patch('secciones/{seccion}/campos/{campo}', 'updateCampo')->name('formulario.secciones.campos.update');
        Route::delete('secciones/{seccion}/campos/{campo}', 'destroyCampo')->name('formulario.secciones.campos.destroy');
        Route::post('secciones/{seccion}/campos/{campo}/mover', 'moverCampo')->name('formulario.secciones.campos.mover');
        Route::post('secciones/{seccion}/mover', 'moverSeccion')->name('formulario.secciones.mover');
        Route::post('secciones/{seccion}/campos/{campo}/condiciones', 'storeCondicion')->name('formulario.secciones.campos.condiciones.store');
        Route::delete('condiciones/{condicion}', 'destroyCondicion')->name('formulario.condiciones.destroy');
        Route::post('grupos-campos', 'storeGrupoCampos')->name('formulario.grupos-campos.store');
        Route::delete('grupos-campos/{grupo}/campos/{campo}', 'destroyCampoGrupo')->name('formulario.grupos-campos.campos.destroy');
        Route::post('grupos-campos/{grupo}/condiciones', 'storeCondicionGrupo')->name('formulario.grupos-campos.condiciones.store');
    });

// Rutas del Catálogo de Trámites

Route::resource('catalogo-tramites', 'App\Http\Controllers\TramiteController');
Route::patch('catalogo-tramites/{catalogo_tramite}/estado', 'App\Http\Controllers\TramiteController@cambiarEstado')
    ->name('catalogo-tramites.estado');

// Rutas del Catálogo de Estados
Route::resource('catalogo-estados', 'App\Http\Controllers\EstadoSolicitudesController');
Route::patch('catalogo-estados/{catalogo_estado}/estado', 'App\Http\Controllers\EstadoSolicitudesController@cambiarEstado')
    ->name('catalogo-estados.estado');

// Rutas del Catálogo de Estados de Formularios

Route::resource('catalogo-estados-formularios', 'App\Http\Controllers\FormularioEstadosController')
    ->parameters(['catalogo-estados-formularios' => 'catalogo_estado_formulario']);
Route::patch('catalogo-estados-formularios/{catalogo_estado_formulario}/estado', 'App\Http\Controllers\FormularioEstadosController@cambiarEstado')
    ->name('catalogo-estados-formularios.estado');
