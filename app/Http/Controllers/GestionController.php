<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BitacoraController; 
use App\Models\Gestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GestionController extends Controller
{
    /**
     * Obtener todas las gestiones (activas e inactivas)
     */
    public function index()
    {
        try {
            $gestiones = Gestion::orderBy('anio', 'desc')
                ->orderBy('semestre', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $gestiones
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las gestiones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear una nueva gestión
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'anio' => 'required|integer|min:2020|max:2030',
                'semestre' => 'required|integer|in:1,2',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after:fecha_inicio'
            ], [
                'anio.required' => 'El año es obligatorio',
                'semestre.in' => 'El semestre debe ser 1 o 2',
                'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio'
            ]);

            // Validar duplicados sin importar estado
            $existe = Gestion::where('anio', $validated['anio'])
                ->where('semestre', $validated['semestre'])
                ->exists();
                
            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una gestión para el año ' . $validated['anio'] . ' y semestre ' . $validated['semestre']
                ], 400);
            }

            DB::beginTransaction();

            $gestion = Gestion::create(array_merge($validated, ['activo' => false]));
            
            /**BitacoraController::registrar(
                'CREAR',
                'Se creó la gestión: ' . $gestion->anio . '-' . $gestion->semestre
            );**/

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Gestión creada exitosamente',
                'data' => $gestion
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la gestión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar una gestión
     */
    public function activar($id)
    {
        try {
            $gestion = Gestion::findOrFail($id);
            
            DB::beginTransaction();
            
            $gestion->activar();
            
            /**BitacoraController::registrar(
                'ACTIVAR',
                'Gestión activada: ' . $gestion->anio . '-' . $gestion->semestre
            );**/

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Gestión activada exitosamente',
                'data' => $gestion
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al activar la gestión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener la gestión activa actual
     */
    public function getActiva()
    {
        try {
            $gestionActiva = Gestion::getActiva();

            if (!$gestionActiva) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay gestión académica activa'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $gestionActiva
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la gestión activa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una gestión (solo si NO está activa)
     */
    public function destroy($id)
    {
        try {
            $gestion = Gestion::findOrFail($id);
            
            if ($gestion->activo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la gestión activa. Active otra gestión primero.'
                ], 400);
            }

            DB::beginTransaction();
            
            /**BitacoraController::registrar(
                'ELIMINAR',
                'Gestión eliminada: ' . $gestion->anio . '-' . $gestion->semestre
            );**/

            $gestion->delete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Gestión eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la gestión',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Actualizar una gestión
     */
    public function update(Request $request, $id)
    {
        try {
            $gestion = Gestion::findOrFail($id);
            
            $validated = $request->validate([
                'anio' => 'required|integer|min:2020|max:2030',
                'semestre' => 'required|integer|in:1,2',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after:fecha_inicio'
            ]);

            // Validar duplicados (excepto la misma gestión)
            $existe = Gestion::where('anio', $validated['anio'])
                ->where('semestre', $validated['semestre'])
                ->where('id_gestion', '!=', $id)
                ->exists();
                
            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe otra gestión con ese año y semestre'
                ], 400);
            }

            DB::beginTransaction();
            
            $gestion->update($validated);
           
            /**BitacoraController::registrar(
                'ACTUALIZAR',
                'Gestión actualizada: ' . $gestion->anio . '-' . $gestion->semestre
            );**/

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Gestión actualizada exitosamente',
                'data' => $gestion
            ]);

        } catch (\Exception $e) {
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Gestión actualizada exitosamente',
                'data' => $gestion
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la gestión',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}