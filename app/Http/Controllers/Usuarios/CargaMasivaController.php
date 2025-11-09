<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\Controller;
use App\Http\Requests\CargaMasivaUsuariosRequest;
use App\Services\CargaMasivaUsuariosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CargaMasivaController extends Controller
{
    protected $cargaMasivaService;

    public function __construct(CargaMasivaUsuariosService $cargaMasivaService)
    {
        $this->cargaMasivaService = $cargaMasivaService;
    }

    /**
     * Procesar carga masiva de usuarios desde archivo CSV
     * 
     * @param CargaMasivaUsuariosRequest $request
     * @return JsonResponse
     */
    public function cargarUsuarios(CargaMasivaUsuariosRequest $request): JsonResponse
    {
        // Obtener usuario autenticado
        $usuario = Auth::user();

        // Obtener archivo
        $archivo = $request->file('archivo');

        // Procesar archivo
        $resultado = $this->cargaMasivaService->procesarArchivo($archivo, $usuario);

        // Determinar código de respuesta HTTP
        $statusCode = $resultado['success'] ? 200 : 422;

        return response()->json($resultado, $statusCode);
    }
}
