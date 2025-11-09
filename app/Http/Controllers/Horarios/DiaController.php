<?php

namespace App\Http\Controllers\Horarios;

use App\Http\Controllers\Controller;
use App\Models\Horarios\Dia;
use Illuminate\Http\Request;

class DiaController extends Controller
{
    /**
     * GET /dias/select
     * Obtener días de la semana para dropdowns.
     */
    public function paraSelect()
    {
        try {
            $dias = Dia::select('id_dia as value', 'nombre as label')
                ->orderBy('id_dia')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $dias,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Error al obtener días de la semana'
            ], 500);
        }
    }
}
