<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GestionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// RUTAS DE PRUEBA - ELIMINAR DESPUÉS
Route::get('/test', function () {
    return response()->json(['message' => 'API funcionando correctamente']);
});

// RUTAS DE GESTIONES - ESTAS SON LAS IMPORTANTES
Route::prefix('/gestiones')->group(function () {
    Route::get('/', [GestionController::class, 'index']);
    Route::post('/', [GestionController::class, 'store']);
    Route::post('/{id}/activar', [GestionController::class, 'activar']);
    Route::get('/activa', [GestionController::class, 'getActiva']);
    Route::delete('/{id}', [GestionController::class, 'destroy']);
});