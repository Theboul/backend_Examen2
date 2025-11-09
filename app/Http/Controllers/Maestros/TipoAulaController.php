<?php

namespace App\Http\Controllers\Maestros;

use App\Http\Controllers\Controller;
use App\Models\Maestros\TipoAula;
use App\Models\Sistema\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TipoAulaController extends Controller
{
    /**
     * GET /tipo-aulas
     * Listar todos los Tipos de Aula (incluye inactivos para gestión).
     */
    public function index(Request $request)
    {
        try {
            $query = TipoAula::query();
            
            // Si no se pide incluir inactivos, solo mostramos los activos
            if (!$request->boolean('incluir_inactivos', false)) {
                 $query->where('activo', true);
            }
            
            $tipos = $query->orderBy('nombre')->get();

            return response()->json([
                'success' => true,
                'data' => $tipos,
                'message' => 'Tipos de aula obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de aula',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /tipo-aulas/select
     * Obtener solo los tipos de aula activos en formato de selección.
     */
    public function paraSelect()
    {
        try {
            $tipos = TipoAula::where('activo', true)
                // Alias de columnas para formato {value: id, label: nombre}
                ->select('id_tipo_aula as value', 'nombre as label') 
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
                'message' => 'Error al obtener tipos de aula para selección'
            ], 500);
        }
    }

    /**
     * POST /tipo-aulas
     * Crear un nuevo Tipo de Aula.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100|unique:tipo_aula,nombre',
            'descripcion' => 'nullable|string',
            'activo' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tipo = TipoAula::create($request->all());

            Bitacora::registrar('CREAR', "Tipo de aula creado: {$tipo->nombre} (ID: {$tipo->id_tipo_aula})");

            return response()->json([
                'success' => true,
                'data' => $tipo,
                'message' => 'Tipo de aula creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear tipo de aula',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /tipo-aulas/{id}
     * Actualizar un Tipo de Aula existente.
     */
    public function update(Request $request, $id)
    {
        $tipo = TipoAula::find($id);
        if (!$tipo) {
            return response()->json(['success' => false, 'message' => 'Tipo de aula no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:100', Rule::unique('tipo_aula', 'nombre')->ignore($id, 'id_tipo_aula')],
            'descripcion' => 'nullable|string',
            'activo' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tipo->update($request->all());

            Bitacora::registrar('ACTUALIZAR', "Tipo de aula actualizado: {$tipo->nombre} (ID: {$tipo->id_tipo_aula})");

            return response()->json([
                'success' => true,
                'data' => $tipo,
                'message' => 'Tipo de aula actualizado exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar tipo de aula',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
