<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GestionController;
use App\Http\Controllers\CarreraController;

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

// Rutas para Carreras
Route::prefix('/carreras')->group(function () {
    Route::get('/', [CarreraController::class, 'index']);
    Route::post('/', [CarreraController::class, 'store']);
    Route::get('/select', [CarreraController::class, 'getCarrerasForSelect']);
    Route::get('/{id}', [CarreraController::class, 'show']);
    Route::put('/{id}', [CarreraController::class, 'update']);
    Route::delete('/{id}', [CarreraController::class, 'destroy']);
    Route::post('/{id}/reactivar', [CarreraController::class, 'reactivar']); // Nueva ruta para reactivar
});