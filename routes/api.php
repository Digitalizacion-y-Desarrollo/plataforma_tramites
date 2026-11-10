<?php

use App\Http\Controllers\ApiSolicitudController;
use App\Http\Controllers\ApiRetysTramiteController;
use App\Http\Controllers\ApiTramiteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['api.key', 'throttle:120,1'])->group(function (): void {
    Route::get('solicitudes', [ApiSolicitudController::class, 'index'])->name('api.v1.solicitudes.index');
    Route::get('tramites', [ApiTramiteController::class, 'index'])->name('api.v1.tramites.index');
    Route::get('retys/tramites/lista', [ApiRetysTramiteController::class, 'index'])->name('api.v1.retys.tramites.index');
    Route::get('retys/tramites', [ApiRetysTramiteController::class, 'show'])->name('api.v1.retys.tramites.show');
});
