<?php

namespace App\Http\Controllers\Horarios;

use App\Http\Controllers\Controller;
use App\Models\Horarios\BloqueHorario;
use Illuminate\Http\Request;

class BloqueHorarioController extends Controller
{
    /**
     * GET /bloques-horario/select
     * Obtener bloques horarios activos en formato de selección.
     */
    public function paraSelect()
    {
        try {
            // Se utiliza CONCAT para mostrar el rango de tiempo en la etiqueta
            $bloques = BloqueHorario::where('activo', true)
                ->selectRaw("id_bloque_horario as value, CONCAT(nombre, ' (', hr_inicio, ' - ', hr_fin, ')') as label")
                ->orderBy('hr_inicio')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $bloques,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Error al obtener bloques horarios'
            ], 500);
        }
    }
}
