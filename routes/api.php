<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GestionController;
use App\Http\Controllers\CarreraController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\AulaController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TipoAulaController;

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

// ========== AUTENTICACIÓN (Públicas) ==========
Route::prefix('/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    
    // Rutas protegidas con Sanctum
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/cambiar-password', [AuthController::class, 'cambiarPasswordPrimerIngreso']);
        Route::post('/toggle-activo/{id}', [AuthController::class, 'toggleActivoCuenta'])->middleware('role:Administrador');
    });
});

// ========== RUTAS PARA ADMINISTRADOR ==========
Route::middleware(['auth:sanctum', 'role:Administrador'])->group(function () {
    
    // Gestiones - CRUD Completo (Solo Admin)
    Route::prefix('/gestiones')->group(function () {
        Route::get('/', [GestionController::class, 'index']);
        Route::post('/', [GestionController::class, 'store']);
        Route::get('/activa', [GestionController::class, 'getActiva']);
        Route::put('/{id}', [GestionController::class, 'update']);
        Route::post('/{id}/activar', [GestionController::class, 'activar']);
        Route::delete('/{id}', [GestionController::class, 'destroy']);
        Route::post('/{id}/reactivar', [GestionController::class, 'reactivar']);
    });

    // Carreras - CRUD Completo (Solo Admin)
    Route::prefix('/carreras')->group(function () {
        Route::get('/', [CarreraController::class, 'index']);
        Route::post('/', [CarreraController::class, 'store']);
        Route::get('/select', [CarreraController::class, 'getCarrerasForSelect']);
        Route::get('/{id}', [CarreraController::class, 'show']);
        Route::put('/{id}', [CarreraController::class, 'update']);
        Route::delete('/{id}', [CarreraController::class, 'destroy']);
        Route::post('/{id}/reactivar', [CarreraController::class, 'reactivar']);
    });

    // Materias - CRUD Completo (Solo Admin)
    Route::prefix('/materias')->group(function () {
        Route::get('/', [MateriaController::class, 'index']);
        Route::get('/select', [MateriaController::class, 'getMateriasForSelect']);
        Route::get('/{id}', [MateriaController::class, 'show']);
        Route::post('/', [MateriaController::class, 'store']);
        Route::put('/{id}', [MateriaController::class, 'update']);
        Route::delete('/{id}', [MateriaController::class, 'destroy']);
        Route::post('/{id}/reactivar', [MateriaController::class, 'reactivar']);
    });

    // Aulas - CRUD Completo (Solo Admin)
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

    Route::prefix('/tipo-aulas')->group(function () {
        Route::get('/', [TipoAulaController::class, 'index']);
        Route::get('/select', [TipoAulaController::class, 'paraSelect']);
        Route::post('/', [TipoAulaController::class, 'store']);
        Route::put('/{id}', [TipoAulaController::class, 'update']);
    });

    // Docentes - CRUD Completo (Solo Admin)
    Route::prefix('/docentes')->group(function () {
        Route::get('/', [DocenteController::class, 'index']);
        Route::post('/', [DocenteController::class, 'store']);
        Route::get('/select', [DocenteController::class, 'getDocentesForSelect']);
        Route::get('/{id}', [DocenteController::class, 'show']);
        Route::put('/{id}', [DocenteController::class, 'update']);
        Route::delete('/{id}', [DocenteController::class, 'destroy']);
        Route::post('/{id}/reactivar', [DocenteController::class, 'reactivar']);
    });
});

// ========== RUTAS PARA ADMINISTRADOR Y COORDINADOR ==========
Route::middleware(['auth:sanctum', 'role:Administrador,Coordinador'])->group(function () {
    
    // Grupos - CRUD (Admin y Coordinador)
    Route::prefix('/grupos')->group(function () {
        Route::get('/', [GrupoController::class, 'index']);
        Route::post('/', [GrupoController::class, 'store']);
        Route::get('/select', [GrupoController::class, 'getGruposForSelect']);
        Route::get('/{id}', [GrupoController::class, 'show']);
        Route::put('/{id}', [GrupoController::class, 'update']);
        Route::delete('/{id}', [GrupoController::class, 'destroy']);
        Route::post('/{id}/reactivar', [GrupoController::class, 'reactivar']);
    });
});

// ========== RUTAS PARA COORDINADOR Y AUTORIDAD (Solo Lectura) ==========
Route::middleware(['auth:sanctum', 'role:Coordinador,Autoridad'])->group(function () {
    
    // Consulta de Gestiones
    Route::get('/gestiones/consulta', [GestionController::class, 'index']);
    Route::get('/gestiones/activa/consulta', [GestionController::class, 'getActiva']);
    
    // Consulta de Carreras
    Route::get('/carreras/consulta', [CarreraController::class, 'index']);
    Route::get('/carreras/select/consulta', [CarreraController::class, 'getCarrerasForSelect']);
    
    // Consulta de Materias
    Route::get('/materias/consulta', [MateriaController::class, 'index']);
    Route::get('/materias/select/consulta', [MateriaController::class, 'getMateriasForSelect']);
    
    // Consulta de Aulas
    Route::get('/aulas/consulta', [AulaController::class, 'index']);
    Route::get('/aulas/select/consulta', [AulaController::class, 'getAulasForSelect']);
    
    // Consulta de Docentes
    Route::get('/docentes/consulta', [DocenteController::class, 'index']);
    Route::get('/docentes/select/consulta', [DocenteController::class, 'getDocentesForSelect']);
});