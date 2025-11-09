<?php

namespace App\Http\Controllers\Maestros;

use App\Http\Controllers\Controller;
use App\Models\Maestros\TipoClase;
use Illuminate\Http\Request;

class TipoClaseController extends Controller
{
    /**
     * GET /tipos-clase/select
     * Obtener tipos de clase activos para dropdowns.
     */
    public function paraSelect()
    {
        try {
            $tipos = TipoClase::where('activo', true)
                ->select('id_tipo_clase as value', 'nombre as label')
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tipos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Error al obtener tipos de clase'
            ], 500);
        }
    }
}
