<?php

namespace App\Http\Controllers\Maestros;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Maestros\Carrera;
use App\Models\Sistema\Bitacora;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CarreraController extends Controller
{
    /**
     * Obtener todas las carreras
     */
    public function index(Request $request)
    {
        try {
            $query = Carrera::query();

            // Filtrar por estado
            if ($request->has('incluir_inactivas') && $request->boolean('incluir_inactivas')) {
                // No filtrar, traer todas
            } else {
                $query->activas(); // Solo activas por defecto
            }

            $carreras = $query->orderBy('nombre')->get();

            return response()->json([
                'success' => true,
                'data' => $carreras,
                'message' => 'Carreras obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las carreras',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear una nueva carrera
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:150|unique:carrera,nombre',
                'codigo' => 'required|string|max:20|unique:carrera,codigo',
                'duracion_anios' => 'required|integer|min:1|max:10'
            ], [
                'nombre.required' => 'El nombre de la carrera es obligatorio',
                'nombre.unique' => 'Ya existe una carrera con este nombre',
                'codigo.required' => 'El código de la carrera es obligatorio',
                'codigo.unique' => 'Ya existe una carrera con este código',
                'duracion_anios.required' => 'La duración en años es obligatoria',
                'duracion_anios.min' => 'La duración mínima es 1 año',
                'duracion_anios.max' => 'La duración máxima es 10 años'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Laravel maneja timestamps automáticamente
            $carrera = Carrera::create([
                'nombre' => $request->nombre,
                'codigo' => $request->codigo,
                'duracion_anios' => $request->duracion_anios,
                'activo' => true
                // fecha_creacion y fecha_modificacion se manejan automáticamente
            ]);

            // Registrar en bitácora
            Bitacora::registrar(
                'CREAR',
                "Carrera creada: {$carrera->nombre} ({$carrera->codigo}) - ID: {$carrera->id_carrera}"
            );
            
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $carrera,
                'message' => 'Carrera creada exitosamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la carrera',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una carrera específica
     */
    public function show($id)
    {
        try {
            $carrera = Carrera::find($id);

            if (!$carrera) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carrera no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $carrera,
                'message' => 'Carrera obtenida exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la carrera',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar una carrera existente
     */
    public function update(Request $request, $id)
    {
        try {
            $carrera = Carrera::find($id);

            if (!$carrera) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carrera no encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:150|unique:carrera,nombre,' . $id . ',id_carrera',
                'codigo' => 'required|string|max:20|unique:carrera,codigo,' . $id . ',id_carrera',
                'duracion_anios' => 'required|integer|min:1|max:10'
            ], [
                'nombre.required' => 'El nombre de la carrera es obligatorio',
                'nombre.unique' => 'Ya existe una carrera con este nombre',
                'codigo.required' => 'El código de la carrera es obligatorio',
                'codigo.unique' => 'Ya existe una carrera con este código',
                'duracion_anios.required' => 'La duración en años es obligatoria',
                'duracion_anios.min' => 'La duración mínima es 1 año',
                'duracion_anios.max' => 'La duración máxima es 10 años'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $carrera->update([
                'nombre' => $request->nombre,
                'codigo' => $request->codigo,
                'duracion_anios' => $request->duracion_anios
            ]);

            // Registrar en bitácora
            Bitacora::registrar(
                'ACTUALIZAR',
                "Carrera actualizada: {$carrera->nombre} ({$carrera->codigo}) - ID: {$carrera->id_carrera}"
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $carrera,
                'message' => 'Carrera actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la carrera',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Desactivar una carrera (eliminación lógica)
     */
    public function destroy($id)
    {
        try {
            $carrera = Carrera::activas()->find($id);

            if (!$carrera) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carrera no encontrada o ya está desactivada'
                ], 404);
            }

            DB::beginTransaction();

            // Verificar si puede ser desactivada
            if (!$carrera->puedeDesactivarse()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede desactivar la carrera porque tiene materias activas asignadas'
                ], 400);
            }

            // Desactivar (soft delete)
            $carrera->update(['activo' => false]);

            // Registrar en bitácora
            Bitacora::registrar(
                'DESACTIVAR',
                "Carrera desactivada: {$carrera->nombre} ({$carrera->codigo}) - ID: {$carrera->id_carrera}"
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Carrera desactivada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar la carrera',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reactivar una carrera
     */
    public function reactivar($id)
    {
        try {
            $carrera = Carrera::where('activo', false)->find($id);

            if (!$carrera) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carrera no encontrada o ya está activa'
                ], 404);
            }

            DB::beginTransaction();

            $carrera->update(['activo' => true]);

            // Registrar en bitácora
            Bitacora::registrar(
                'REACTIVAR',
                "Carrera reactivada: {$carrera->nombre} ({$carrera->codigo}) - ID: {$carrera->id_carrera}"
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $carrera,
                'message' => 'Carrera reactivada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al reactivar la carrera',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener carreras para select/combobox
     */
    public function getCarrerasForSelect()
    {
        try {
            $carreras = Carrera::activas()
                ->select('id_carrera', 'nombre', 'codigo')
                ->orderBy('nombre')
                ->get()
                ->map(function ($carrera) {
                    return [
                        'value' => $carrera->id_carrera,
                        'label' => $carrera->nombre . ' (' . $carrera->codigo . ')'
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $carreras
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener carreras para select',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
