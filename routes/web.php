<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});


Route::resource('formulario', 'App\Http\Controllers\FormularioController');
Route::post('formulario/{formulario}/secciones', 'App\Http\Controllers\FormularioController@storeSeccion')->name('formulario.secciones.store');
Route::delete('formulario/{formulario}/secciones/{seccion}', 'App\Http\Controllers\FormularioController@destroySeccion')->name('formulario.secciones.destroy');
Route::post('formulario/{formulario}/campos/orden', 'App\Http\Controllers\FormularioController@ordenarCampos')->name('formulario.campos.ordenar');
Route::post('formulario/{formulario}/secciones/{seccion}/campos', 'App\Http\Controllers\FormularioController@storeCampo')->name('formulario.secciones.campos.store');
Route::patch('formulario/{formulario}/secciones/{seccion}/campos/{campo}', 'App\Http\Controllers\FormularioController@updateCampo')->name('formulario.secciones.campos.update');
Route::delete('formulario/{formulario}/secciones/{seccion}/campos/{campo}', 'App\Http\Controllers\FormularioController@destroyCampo')->name('formulario.secciones.campos.destroy');
Route::post('formulario/{formulario}/secciones/{seccion}/campos/{campo}/mover', 'App\Http\Controllers\FormularioController@moverCampo')->name('formulario.secciones.campos.mover');
Route::post('formulario/{formulario}/secciones/{seccion}/mover', 'App\Http\Controllers\FormularioController@moverSeccion')->name('formulario.secciones.mover');
Route::post('formulario/{formulario}/secciones/{seccion}/campos/{campo}/condiciones', 'App\Http\Controllers\FormularioController@storeCondicion')->name('formulario.secciones.campos.condiciones.store');
Route::delete('formulario/{formulario}/condiciones/{condicion}', 'App\Http\Controllers\FormularioController@destroyCondicion')->name('formulario.condiciones.destroy');
Route::post('formulario/{formulario}/grupos-campos', 'App\Http\Controllers\FormularioController@storeGrupoCampos')->name('formulario.grupos-campos.store');
Route::delete('formulario/{formulario}/grupos-campos/{grupo}/campos/{campo}', 'App\Http\Controllers\FormularioController@destroyCampoGrupo')->name('formulario.grupos-campos.campos.destroy');
Route::post('formulario/{formulario}/grupos-campos/{grupo}/condiciones', 'App\Http\Controllers\FormularioController@storeCondicionGrupo')->name('formulario.grupos-campos.condiciones.store');
