<?php

namespace App\Http\Controllers\Maestros;

use App\Http\Controllers\Controller;
use App\Models\Maestros\Grupo;
use App\Models\Maestros\MateriaGrupo;
use App\Models\Sistema\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GrupoController extends Controller
{
    /**
     * Listar todos los grupos con filtros opcionales
     */
    public function index(Request $request)
    {
        try {
            $query = Grupo::with(['materia']);

            // Filtro por estado (activo/inactivo)
            if ($request->has('activo')) {
                $query->where('activo', $request->boolean('activo'));
            }

            // Filtro por materia
            if ($request->has('id_materia')) {
                $query->where('id_materia', $request->id_materia);
            }

            // Filtro por nombre
            if ($request->has('nombre')) {
                $query->where('nombre', 'ILIKE', '%' . $request->nombre . '%');
            }

            // Incluir inactivos
            if (!$request->has('incluir_inactivos') || !$request->boolean('incluir_inactivos')) {
                $query->where('activo', true);
            }

            $grupos = $query->orderBy('nombre')->get();

            return response()->json([
                'success' => true,
                'data' => $grupos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener grupos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo grupo
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_materia' => 'required|integer|exists:materia,id_materia',
                'nombre' => [
                    'required',
                    'string',
                    'max:50',
                    // Validar unicidad del nombre por materia
                    Rule::unique('grupo', 'nombre')
                        ->where('id_materia', $request->id_materia)
                        ->where('activo', true)
                ],
                'descripcion' => 'nullable|string',
                'capacidad_maxima' => 'required|integer|min:1',
                'cupos' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validar que cupos no exceda capacidad_maxima
            $cupos = $request->cupos ?? 0;
            if ($cupos > $request->capacidad_maxima) {
                return response()->json([
                    'success' => false,
                    'message' => 'Los cupos actuales no pueden exceder la capacidad máxima'
                ], 422);
            }

            // Preparar datos para crear
            $data = [
                'id_materia' => $request->id_materia,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'capacidad_maxima' => $request->capacidad_maxima,
                'cupos' => $cupos,
                'activo' => true,
            ];

            // Solo agregar creado_por si hay usuario autenticado
            if (auth()->check()) {
                $data['creado_por'] = auth()->user()->id_perfil_usuario;
            }

            $grupo = Grupo::create($data);

            // Registrar en bitácora
            Bitacora::registrar(
                'CREAR',
                "Grupo creado: {$grupo->nombre} - ID: {$grupo->id_grupo}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Grupo creado exitosamente',
                'data' => $grupo->load('materia')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el grupo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un grupo específico
     */
    public function show($id)
    {
        try {
            $grupo = Grupo::with(['materia', 'materiaGrupos.gestion'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $grupo
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Grupo no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Actualizar un grupo
     */
    public function update(Request $request, $id)
    {
        try {
            $grupo = Grupo::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'id_materia' => 'sometimes|required|integer|exists:materia,id_materia',
                'nombre' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:50',
                    // Validar unicidad del nombre por materia (excluyendo el actual)
                    Rule::unique('grupo', 'nombre')
                        ->where('id_materia', $request->id_materia ?? $grupo->id_materia)
                        ->where('activo', true)
                        ->ignore($id, 'id_grupo')
                ],
                'descripcion' => 'nullable|string',
                'capacidad_maxima' => 'sometimes|required|integer|min:1',
                'cupos' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validar que cupos no exceda capacidad_maxima
            $capacidadMaxima = $request->capacidad_maxima ?? $grupo->capacidad_maxima;
            $cupos = $request->cupos ?? $grupo->cupos;
            
            if ($cupos > $capacidadMaxima) {
                return response()->json([
                    'success' => false,
                    'message' => 'Los cupos actuales no pueden exceder la capacidad máxima'
                ], 422);
            }

            $grupo->update($request->only([
                'id_materia',
                'nombre',
                'descripcion',
                'capacidad_maxima',
                'cupos',
            ]));

            // Registrar en bitácora
            Bitacora::registrar(
                'ACTUALIZAR',
                "Grupo actualizado: {$grupo->nombre} - ID: {$grupo->id_grupo}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Grupo actualizado exitosamente',
                'data' => $grupo->load('materia')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el grupo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Desactivar un grupo (eliminación lógica)
     */
    public function destroy($id)
    {
        try {
            $grupo = Grupo::findOrFail($id);

            // Verificar si puede ser desactivado
            if (!$grupo->puedeDesactivarse()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede desactivar el grupo porque tiene asignaciones activas (materia-grupo-gestión)'
                ], 422);
            }

            $grupo->update(['activo' => false]);

            // Registrar en bitácora
            Bitacora::registrar(
                'DESACTIVAR',
                "Grupo desactivado: {$grupo->nombre} - ID: {$grupo->id_grupo}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Grupo desactivado exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar el grupo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reactivar un grupo
     */
    public function reactivar($id)
    {
        try {
            $grupo = Grupo::findOrFail($id);

            // Verificar que no exista otro grupo con el mismo nombre activo para la misma materia
            $existeActivo = Grupo::where('nombre', $grupo->nombre)
                ->where('id_materia', $grupo->id_materia)
                ->where('activo', true)
                ->where('id_grupo', '!=', $id)
                ->exists();

            if ($existeActivo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un grupo activo con ese nombre para esta materia'
                ], 422);
            }

            $grupo->update(['activo' => true]);

            // Registrar en bitácora
            Bitacora::registrar(
                'REACTIVAR',
                "Grupo reactivado: {$grupo->nombre} - ID: {$grupo->id_grupo}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Grupo reactivado exitosamente',
                'data' => $grupo->load('materia')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reactivar el grupo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener grupos para select (solo activos)
     */
    public function getGruposForSelect(Request $request)
    {
        try {
            $query = Grupo::activos()
                ->select('id_grupo', 'nombre', 'id_materia', 'cupos', 'capacidad_maxima');

            // Filtrar por materia si se proporciona
            if ($request->has('id_materia')) {
                $query->where('id_materia', $request->id_materia);
            }

            $grupos = $query->orderBy('nombre')->get();

            return response()->json([
                'success' => true,
                'data' => $grupos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener grupos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
