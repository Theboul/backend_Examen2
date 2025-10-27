<?php

use Illuminate\Support\Facades\Route;


Route::prefix('api')
    ->middleware('api')
    ->group(base_path('routes/api.php'));


Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    return response()->json(['message' => 'API funcionando correctamente']);
});

