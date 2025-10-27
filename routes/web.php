<?php

use Illuminate\Support\Facades\Route;

Route::prefix('api')
    ->middleware('api')
    ->group(base_path('routes/api.php'));

Route::get('/form-simple', function() {
    return view('base', [
        'action' => '/procesar',
        'content' => '<input type="text" name="campo">'
    ]);
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    return response()->json(['message' => 'API funcionando correctamente']);
});

// Ruta para formulario de carreras (API)
Route::get('/form-carrera', function() {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Crear Carrera</title>
    </head>
    <body>
        <h2>Crear Nueva Carrera</h2>
        <form method="POST" action="/api/carreras">
            <div>
                <input type="text" name="nombre" placeholder="Nombre de la carrera" required>
            </div>
            <div>
                <input type="text" name="codigo" placeholder="Código" required>
            </div>
            <div>
                <input type="number" name="duracion_anios" placeholder="Duración en años" required>
            </div>
            <button type="submit">Crear Carrera</button>
        </form>
        
        <script>
            // Manejar la respuesta
            document.querySelector("form").addEventListener("submit", async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const data = Object.fromEntries(formData);
                
                try {
                    const response = await fetch("/api/carreras", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify(data)
                    });
                    
                    const result = await response.json();
                    alert(result.message);
                    
                    if (result.success) {
                        this.reset();
                    }
                } catch (error) {
                    alert("Error: " + error.message);
                }
            });
        </script>
    </body>
    </html>
    ';
});

// Ruta POST para el formulario simple (si aún la necesitas)
Route::post('/procesar', function() {
    return "¡POST recibido! Campo: " . request()->input('campo');
});