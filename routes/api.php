<?php

use App\Http\Controllers\ApiSolicitudController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['api.key', 'throttle:120,1'])->group(function (): void {
    Route::get('solicitudes', [ApiSolicitudController::class, 'index'])->name('api.v1.solicitudes.index');
});
