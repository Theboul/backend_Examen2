<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AulaController extends Controller
{
    /**
     * Obtener todas las aulas activas
     */
    public function index(Request $request)
    {
        try {
            $query = Aula::query()->with('tipoAula:id_tipo_aula,nombre');

            // Filtros
            if ($request->boolean('disponibles', false)) {
                $query->disponibles();
            } elseif ($request->boolean('en_mantenimiento', false)) {
                $query->enMantenimiento();
            } elseif ($request->has('incluir_inactivas') && $request->boolean('incluir_inactivas')) {
                $query = Aula::withInactive()->with('tipoAula:id_tipo_aula,nombre');
            } else {
                $query->activas();
            }

            $aulas = $query->orderBy('piso')->orderBy('nombre')->get();

            return response()->json([
                'success' => true,
                'data' => $aulas,
                'message' => 'Aulas obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las aulas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un aula específica
     */
    public function show($id)
    {
        try {
            $aula = Aula::withInactive()->with('tipoAula')->find($id);

            if (!$aula) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aula no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $aula,
                'message' => 'Aula obtenida exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el aula',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear una nueva aula
     */
    public function store(Request $request)
    {
        try {
            // Validar datos de entrada según CU5
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100|unique:aula,nombre',
                'capacidad' => 'required|integer|min:1',
                'piso' => 'nullable|integer',
                'id_tipo_aula' => 'required|integer|exists:tipo_aula,id_tipo_aula',
                'mantenimiento' => 'nullable|boolean',
            ], [
                'nombre.required' => 'El nombre del aula es obligatorio',
                'nombre.unique' => 'Ya existe un aula con el nombre ingresado',
                'capacidad.required' => 'La capacidad del aula es obligatoria',
                'capacidad.min' => 'La capacidad del aula debe ser mayor a 0',
                'id_tipo_aula.required' => 'El tipo de aula es obligatorio',
                'id_tipo_aula.exists' => 'El tipo de aula seleccionado no existe',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Crear el aula
            $aula = Aula::create([
                'nombre' => $request->nombre,
                'capacidad' => $request->capacidad,
                'piso' => $request->piso ?? 0,
                'id_tipo_aula' => $request->id_tipo_aula,
                'mantenimiento' => $request->mantenimiento ?? false,
                'activo' => true,
            ]);

            // Registrar en bitácora
            //$this->registrarBitacora('Aula creada: ' . $aula->nombre);

            DB::commit();

            // Cargar la relación para la respuesta
            $aula->load('tipoAula');

            return response()->json([
                'success' => true,
                'data' => $aula,
                'message' => 'Aula creada exitosamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el aula',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un aula existente
     */
    public function update(Request $request, $id)
    {
        try {
            $aula = Aula::withInactive()->find($id);

            if (!$aula) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aula no encontrada'
                ], 404);
            }

            // Validar datos de entrada (ignorar unique para el mismo registro)
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100|unique:aula,nombre,' . $id . ',id_aula',
                'capacidad' => 'required|integer|min:1',
                'piso' => 'nullable|integer',
                'id_tipo_aula' => 'required|integer|exists:tipo_aula,id_tipo_aula',
                'mantenimiento' => 'nullable|boolean',
            ], [
                'nombre.required' => 'El nombre del aula es obligatorio',
                'nombre.unique' => 'Ya existe un aula con el nombre ingresado',
                'capacidad.required' => 'La capacidad del aula es obligatoria',
                'capacidad.min' => 'La capacidad del aula debe ser mayor a 0',
                'id_tipo_aula.required' => 'El tipo de aula es obligatorio',
                'id_tipo_aula.exists' => 'El tipo de aula seleccionado no existe',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Actualizar el aula
            $aula->update([
                'nombre' => $request->nombre,
                'capacidad' => $request->capacidad,
                'piso' => $request->piso ?? $aula->piso,
                'id_tipo_aula' => $request->id_tipo_aula,
                'mantenimiento' => $request->mantenimiento ?? $aula->mantenimiento,
            ]);

            // Registrar en bitácora
            //$this->registrarBitacora('Aula actualizada: ' . $aula->nombre);

            DB::commit();

            // Cargar la relación para la respuesta
            $aula->load('tipoAula');

            return response()->json([
                'success' => true,
                'data' => $aula,
                'message' => 'Aula actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el aula',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Desactivar un aula (eliminación lógica)
     */
    public function destroy($id)
    {
        try {
            $aula = Aula::activas()->find($id);

            if (!$aula) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aula no encontrada o ya está desactivada'
                ], 404);
            }

            DB::beginTransaction();

            // Verificar si el aula puede ser desactivada
            if (!$aula->puedeDesactivarse()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede desactivar el aula porque tiene horarios asignados'
                ], 400);
            }

            // Desactivar el aula (eliminación lógica)
            $aula->update(['activo' => false]);

            // Registrar en bitácora
            //$this->registrarBitacora('Aula desactivada: ' . $aula->nombre);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Aula desactivada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar el aula',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reactivar un aula previamente desactivada
     */
    public function reactivar($id)
    {
        try {
            $aula = Aula::withInactive()->where('activo', false)->find($id);

            if (!$aula) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aula no encontrada o ya está activa'
                ], 404);
            }

            DB::beginTransaction();

            // Reactivar el aula
            $aula->update(['activo' => true]);

            // Registrar en bitácora
            //$this->registrarBitacora('Aula reactivada: ' . $aula->nombre);

            DB::commit();

            // Cargar la relación para la respuesta
            $aula->load('tipoAula');

            return response()->json([
                'success' => true,
                'data' => $aula,
                'message' => 'Aula reactivada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al reactivar el aula',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener aulas para select/combobox (solo activas y disponibles)
     */
    public function getAulasForSelect(Request $request)
    {
        try {
            $query = Aula::disponibles()
                ->select('id_aula', 'nombre', 'capacidad', 'piso')
                ->orderBy('piso')
                ->orderBy('nombre');

            // Filtrar por tipo si se proporciona
            if ($request->has('id_tipo_aula')) {
                $query->where('id_tipo_aula', $request->id_tipo_aula);
            }

            $aulas = $query->get()->map(function ($aula) {
                return [
                    'value' => $aula->id_aula,
                    'label' => $aula->nombre . ' (Piso ' . $aula->piso . ', Cap: ' . $aula->capacidad . ')'
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $aulas,
                'message' => 'Aulas para select obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener aulas para select',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar estado de mantenimiento del aula
     */
    public function toggleMantenimiento($id)
    {
        try {
            $aula = Aula::activas()->find($id);

            if (!$aula) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aula no encontrada'
                ], 404);
            }

            DB::beginTransaction();

            $nuevoEstado = !$aula->mantenimiento;
            $aula->update(['mantenimiento' => $nuevoEstado]);

            $accion = $nuevoEstado ? 'Aula puesta en mantenimiento' : 'Aula sacada de mantenimiento';
            $this->registrarBitacora($accion . ': ' . $aula->nombre);

            DB::commit();

            $aula->load('tipoAula');

            return response()->json([
                'success' => true,
                'data' => $aula,
                'message' => $accion . ' exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado de mantenimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Función auxiliar para registrar en bitácora
     */
    private function registrarBitacora($accion)
    {
        try {
            DB::table('bitacora')->insert([
                'id_perfil_usuario' => auth()->user()->id_perfil_usuario ?? 1, // temporal
                'accion' => $accion,
                'fecha' => now(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al registrar bitácora: ' . $e->getMessage());
        }
    }
}
