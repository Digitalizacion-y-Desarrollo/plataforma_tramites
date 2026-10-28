<?php

use App\Http\Controllers\AccesosSesionController;
use App\Http\Controllers\AreaOperativaController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\CanalAyudaController;
use App\Http\Controllers\ConfiguracionGeneralController;
use App\Http\Controllers\CorreccionDocumentoController;
use App\Http\Controllers\DocumentoEstadosController;
use App\Http\Controllers\EstadoSolicitudesController;
use App\Http\Controllers\FormularioController;
use App\Http\Controllers\FormularioEstadosController;
use App\Http\Controllers\NotificacionEstadosController;
use App\Http\Controllers\PortadaController;
use App\Http\Controllers\PublicoController;
use App\Http\Controllers\ServicioInteresController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\TramiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicoController::class, 'index'])->name('portal.publico');
Route::get('/admin/login', [AccesosSesionController::class, 'create'])->name('accesos.login');
Route::post('/admin/login', [AccesosSesionController::class, 'store'])->name('accesos.login.store');
Route::prefix('tramites')->name('portal.tramites.')->controller(PublicoController::class)->group(function () {
    Route::get('/', 'tramites')->name('index');
    Route::get('/{tramite}/iniciar', 'iniciar')->name('iniciar');
    Route::post('/{tramite}/solicitudes', 'storeSolicitud')->name('solicitudes.store');
    Route::get('/{tramite}', 'detalle')->name('detalle');
});
Route::prefix('consulta')->name('consulta.')->controller(PublicoController::class)->group(function () {
    Route::post('/token', 'solicitarToken')->middleware('throttle:5,15')->name('token.solicitar');
    Route::post('/verificar', 'verificarToken')->middleware('throttle:10,1')->name('token.verificar');
    Route::post('/cancelar', 'cancelarConsultaPendiente')->name('cancelar');
    Route::get('/solicitudes', 'solicitudesConsulta')->name('solicitudes');
    Route::get('/solicitudes/{solicitud}', 'detalleSolicitudConsulta')->name('solicitudes.detalle');
    Route::post('/solicitudes/{solicitud}/comentarios', 'responderComentario')->name('solicitudes.comentarios.store');
    Route::post('/cerrar', 'cerrarConsulta')->name('cerrar');
    Route::get('/documentos/{documento}', 'descargarDocumentoEmitido')->name('documentos.descargar');
    Route::get('/pagos/{pago}/referencia', 'descargarReferenciaPago')->name('pagos.referencia.descargar');
    Route::post('/pagos/{pago}/comprobante', 'subirComprobantePago')->name('pagos.comprobante.subir');
});
Route::prefix('correcciones')->name('correcciones.')->controller(CorreccionDocumentoController::class)->group(function () {
    Route::get('/{token}', 'mostrar')->name('mostrar');
    Route::post('/{token}/acceso', 'verificar')->name('verificar');
    Route::post('/{token}/documentos', 'actualizar')->name('actualizar');
});

Route::prefix('admin')->middleware('accesos.auth')->group(function () {
    Route::post('/logout', [AccesosSesionController::class, 'destroy'])->name('accesos.logout');
    Route::get('/', [SolicitudController::class, 'dashboard'])->middleware('accesos.role:Colaborador,superadmin,superuser')->name('admin.dashboard');
    Route::prefix('solicitudes')->name('solicitudes.')->middleware('accesos.role:Colaborador,superadmin,superuser')->group(function () {
        Route::get('/', [SolicitudController::class, 'index'])->name('index');
        Route::get('/areas', [AreaOperativaController::class, 'index'])->middleware('accesos.role:superadmin,superuser')->name('areas.index');
        Route::post('/areas', [AreaOperativaController::class, 'store'])->middleware('accesos.role:superadmin,superuser')->name('areas.store');
        Route::patch('/areas/{area}', [AreaOperativaController::class, 'update'])->middleware('accesos.role:superadmin,superuser')->name('areas.update');
        Route::middleware('accesos.solicitud-area')->group(function () {
            Route::get('/{solicitud}', [SolicitudController::class, 'show'])->name('show');
            Route::get('/{solicitud}/referencia-pago/previsualizar', [SolicitudController::class, 'previsualizarReferenciaPago'])->name('pagos.previsualizar');
            Route::get('/{solicitud}/pagos/comprobante', [SolicitudController::class, 'descargarComprobantePago'])->name('pagos.comprobante.descargar');
            Route::get('/{solicitud}/documentos/{documento}', [SolicitudController::class, 'descargarDocumento'])->name('documentos.descargar');
            Route::get('/{solicitud}/documentos/{documento}/previsualizar', [SolicitudController::class, 'previsualizarDocumento'])->name('documentos.previsualizar');
            Route::get('/{solicitud}/archivos/{campo}', [SolicitudController::class, 'descargarArchivoRespuesta'])->name('archivos.descargar');
            Route::get('/{solicitud}/archivos/{campo}/previsualizar', [SolicitudController::class, 'previsualizarArchivoRespuesta'])->name('archivos.previsualizar');
        });
    });
    Route::prefix('solicitudes/{solicitud}')->middleware(['accesos.role:superadmin,superuser', 'accesos.solicitud-area'])->group(function () {
        Route::put('/datos-personales', [SolicitudController::class, 'actualizarDatosPersonales'])->name('solicitudes.datos-personales.actualizar');
        Route::put('/secciones/{seccion}/respuestas', [SolicitudController::class, 'actualizarRespuestas'])->name('solicitudes.respuestas.actualizar');
        Route::post('/aprobar', [SolicitudController::class, 'aprobar'])->name('solicitudes.aprobar');
        Route::post('/rechazar', [SolicitudController::class, 'rechazar'])->name('solicitudes.rechazar');
        Route::post('/correcciones', [SolicitudController::class, 'solicitarCorreccion'])->name('solicitudes.correcciones.solicitar');
        Route::post('/validar', [SolicitudController::class, 'validarParaPago'])->name('solicitudes.validar');
        Route::post('/ficha-pago', [SolicitudController::class, 'enviarFichaPago'])->name('solicitudes.pagos.ficha.enviar');
        Route::post('/ficha-pago/reemplazar', [SolicitudController::class, 'reemplazarFichaPago'])->name('solicitudes.pagos.ficha.reemplazar');
        Route::post('/referencia-pago', [SolicitudController::class, 'generarReferenciaPago'])->name('solicitudes.pagos.generar');
        Route::post('/validar-pago', [SolicitudController::class, 'validarPago'])->name('solicitudes.pagos.validar');
        Route::post('/comentarios', [SolicitudController::class, 'comentar'])->name('solicitudes.comentarios.store');
    });
    Route::middleware('accesos.role:superadmin,superuser')->group(function () {
        Route::resource('servicios-interes', ServicioInteresController::class)->except('show')->parameters(['servicios-interes' => 'servicio_interes']);
        Route::get('configuracion/canales-ayuda', [CanalAyudaController::class, 'edit'])->name('canales-ayuda.edit');
        Route::put('configuracion/canales-ayuda', [CanalAyudaController::class, 'update'])->name('canales-ayuda.update');
        Route::get('configuracion/portada', [PortadaController::class, 'edit'])->name('portada.edit');
        Route::put('configuracion/portada', [PortadaController::class, 'update'])->name('portada.update');
    });
    Route::middleware('accesos.role:superuser')->group(function () {
        Route::prefix('dependencias/{dependencia}')
            ->name('dependencias.')
            ->whereIn('dependencia', ['desarrollo-urbano', 'secretaria-del-ayuntamiento'])
            ->group(function () {
                Route::get('/resumen', [SolicitudController::class, 'resumenDependencia'])->name('resumen');
                Route::get('/solicitudes', [SolicitudController::class, 'indexDependencia'])->name('solicitudes.index');
            });
        Route::resource('formulario', FormularioController::class);
        Route::prefix('formulario/{formulario}')->controller(FormularioController::class)->group(function () {
            Route::get('duplicar', 'createDuplicado')->name('formulario.duplicar');
            Route::post('duplicar', 'storeDuplicado')->name('formulario.duplicar.store');
            Route::post('secciones', 'storeSeccion')->name('formulario.secciones.store');
            Route::patch('secciones/{seccion}', 'updateSeccion')->name('formulario.secciones.update');
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
        Route::resource('catalogo-tramites', TramiteController::class);
        Route::patch('catalogo-tramites/{catalogo_tramite}/estado', [TramiteController::class, 'cambiarEstado'])->name('catalogo-tramites.estado');
        Route::get('configuracion/general', [ConfiguracionGeneralController::class, 'edit'])->name('configuracion-general.edit');
        Route::put('configuracion/general', [ConfiguracionGeneralController::class, 'update'])->name('configuracion-general.update');
        Route::resource('catalogo-estados', EstadoSolicitudesController::class);
        Route::patch('catalogo-estados/{catalogo_estado}/estado', [EstadoSolicitudesController::class, 'cambiarEstado'])->name('catalogo-estados.estado');
        Route::resource('catalogo-estados-formularios', FormularioEstadosController::class)->parameters(['catalogo-estados-formularios' => 'catalogo_estado_formulario']);
        Route::patch('catalogo-estados-formularios/{catalogo_estado_formulario}/estado', [FormularioEstadosController::class, 'cambiarEstado'])->name('catalogo-estados-formularios.estado');
        Route::resource('catalogo-estados-documentos', DocumentoEstadosController::class)->parameters(['catalogo-estados-documentos' => 'catalogo_estado_documento']);
        Route::patch('catalogo-estados-documentos/{catalogo_estado_documento}/estado', [DocumentoEstadosController::class, 'cambiarEstado'])->name('catalogo-estados-documentos.estado');
        Route::resource('catalogo-estados-notificaciones', NotificacionEstadosController::class)->parameters(['catalogo-estados-notificaciones' => 'catalogo_estado_notificacion']);
        Route::patch('catalogo-estados-notificaciones/{catalogo_estado_notificacion}/estado', [NotificacionEstadosController::class, 'cambiarEstado'])->name('catalogo-estados-notificaciones.estado');
    });
    Route::get('auditoria', [AuditoriaController::class, 'index'])->middleware('accesos.role:superadmin,superuser')->name('auditoria.index');
});
