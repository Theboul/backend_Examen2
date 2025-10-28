<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GestionController;
use App\Http\Controllers\CarreraController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\AulaController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\DocenteController;

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

// Rutas para Gestiones
Route::prefix('/gestiones')->group(function () {
    Route::get('/', [GestionController::class, 'index']);
    Route::post('/', [GestionController::class, 'store']);
    Route::get('/activa', [GestionController::class, 'getActiva']);
    Route::put('/{id}', [GestionController::class, 'update']);
    Route::post('/{id}/activar', [GestionController::class, 'activar']);
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

//Rutas para Materias
Route::prefix('/materias')->group(function () {
    Route::get('/', [MateriaController::class, 'index']);
    Route::get('/select', [MateriaController::class, 'getMateriasForSelect']);
    Route::get('/{id}', [MateriaController::class, 'show']);
    Route::post('/', [MateriaController::class, 'store']);
    Route::put('/{id}', [MateriaController::class, 'update']);
    Route::delete('/{id}', [MateriaController::class, 'destroy']);
    Route::post('/{id}/reactivar', [MateriaController::class, 'reactivar']);
});

// Rutas para Aulas
Route::prefix('/aulas')->group(function () {
    Route::get('/', [AulaController::class, 'index']);
    Route::post('/', [AulaController::class, 'store']);
    Route::get('/select', [AulaController::class, 'getAulasForSelect']);
    Route::get('/{id}', [AulaController::class, 'show']);
    Route::put('/{id}', [AulaController::class, 'update']);
    Route::delete('/{id}', [AulaController::class, 'destroy']);
    Route::post('/{id}/reactivar', [AulaController::class, 'reactivar']);
    Route::post('/{id}/toggle-mantenimiento', [AulaController::class, 'toggleMantenimiento']);
});

// Rutas para Grupos
Route::prefix('/grupos')->group(function () {
    Route::get('/', [GrupoController::class, 'index']);
    Route::post('/', [GrupoController::class, 'store']);
    Route::get('/select', [GrupoController::class, 'getGruposForSelect']);
    Route::get('/{id}', [GrupoController::class, 'show']);
    Route::put('/{id}', [GrupoController::class, 'update']);
    Route::delete('/{id}', [GrupoController::class, 'destroy']);
    Route::post('/{id}/reactivar', [GrupoController::class, 'reactivar']);
});

// Rutas para Docentes
Route::prefix('/docentes')->group(function () {
    Route::get('/', [DocenteController::class, 'index']);
    Route::post('/', [DocenteController::class, 'store']);
    Route::get('/select', [DocenteController::class, 'getDocentesForSelect']);
    Route::get('/{id}', [DocenteController::class, 'show']);
    Route::put('/{id}', [DocenteController::class, 'update']);
    Route::delete('/{id}', [DocenteController::class, 'destroy']);
    Route::post('/{id}/reactivar', [DocenteController::class, 'reactivar']);
});