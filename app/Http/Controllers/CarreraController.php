<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Carrera;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CarreraController extends Controller
{
    /**
     * Obtener todas las carreras activas
     */
    public function index(Request $request)
    {
        try {
            $query = Carrera::activas();

            // Si se solicita incluir inactivas
            if ($request->has('incluir_inactivas') && $request->boolean('incluir_inactivas')) {
                $query = Carrera::withInactive();
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
            // Validar datos de entrada
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

            // Crear la carrera
            $carrera = Carrera::create([
                'nombre' => $request->nombre,
                'codigo' => $request->codigo,
                'duracion_anios' => $request->duracion_anios,
                'activo' => true,
                'fecha_creacion' => now(),
                'fecha_modificacion' => now()
            ]);

            // Registrar en bitácora
            $this->registrarBitacora('Carrera creada: ' . $carrera->nombre);

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
            $carrera = Carrera::withInactive()->find($id);

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
            $carrera = Carrera::withInactive()->find($id);

            if (!$carrera) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carrera no encontrada'
                ], 404);
            }

            // Validar datos de entrada (ignorar unique para el mismo registro)
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

            // Actualizar la carrera
            $carrera->update([
                'nombre' => $request->nombre,
                'codigo' => $request->codigo,
                'duracion_anios' => $request->duracion_anios,
                'fecha_modificacion' => now()
            ]);

            // Registrar en bitácora
            $this->registrarBitacora('Carrera actualizada: ' . $carrera->nombre);

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

            // Verificar si la carrera tiene materias activas usando el método del modelo
            if (!$carrera->puedeDesactivarse()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede desactivar la carrera porque tiene materias activas asignadas'
                ], 400);
            }

            // Desactivar la carrera (eliminación lógica)
            $carrera->update([
                'activo' => false,
                'fecha_modificacion' => now()
            ]);

            // Registrar en bitácora
            $this->registrarBitacora('Carrera desactivada: ' . $carrera->nombre);

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
     * Reactivar una carrera previamente desactivada
     */
    public function reactivar($id)
    {
        try {
            $carrera = Carrera::withInactive()->where('activo', false)->find($id);

            if (!$carrera) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carrera no encontrada o ya está activa'
                ], 404);
            }

            DB::beginTransaction();

            // Reactivar la carrera
            $carrera->update([
                'activo' => true,
                'fecha_modificacion' => now()
            ]);

            // Registrar en bitácora
            $this->registrarBitacora('Carrera reactivada: ' . $carrera->nombre);

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
     * Obtener carreras para select/combobox (solo activas)
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
                'data' => $carreras,
                'message' => 'Carreras para select obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener carreras para select',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Función auxiliar para registrar en bitácora
     */
    private function registrarBitacora($accion)
    {
        // Implementación de bitácora según tu estructura
        \Log::info('Bitácora: ' . $accion);
        
        // Ejemplo con tu tabla bitacora:
        /*
        DB::table('bitacora')->insert([
            'id_perfil_usuario' => auth()->id() ?? 1, // Temporal para testing
            'accion' => $accion,
            'fecha' => now()
        ]);
        */
    }
}
