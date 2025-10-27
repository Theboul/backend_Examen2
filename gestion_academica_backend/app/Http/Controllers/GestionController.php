<?php

namespace App\Http\Controllers;

use App\Models\Gestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GestionController extends Controller
{
    /**
     * Obtener todas las gestiones
     */
    public function index()
    {
        try {
            $gestiones = Gestion::where('activo', true)
                ->orderBy('anio', 'desc')
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
            // Validar datos de entrada
            $validated = $request->validate([
                'anio' => 'required|integer|min:2020|max:2030',
                'semestre' => 'required|integer|in:1,2',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after:fecha_inicio'
            ]);

            // Validar que no exista duplicado (mismo año y semestre)
            $existe = Gestion::where('anio', $validated['anio'])
                ->where('semestre', $validated['semestre'])
                ->where('activo', true)
                ->exists();
                
            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una gestión para el año ' . $validated['anio'] . ' y semestre ' . $validated['semestre']
                ], 400);
            }

            // Crear la gestión
            $gestion = Gestion::create($validated);

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
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la gestión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar una gestión (solo una puede estar activa)
     */
    public function activar($id)
    {
        try {
            $gestion = Gestion::where('id_gestion', $id)->where('activo', true)->firstOrFail();
            $gestion->activar();

            return response()->json([
                'success' => true,
                'message' => 'Gestión activada exitosamente'
            ]);

        } catch (\Exception $e) {
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
     * Eliminar una gestión
     */
    public function destroy($id)
    {
        try {
            $gestion = Gestion::where('id_gestion', $id)->where('activo', true)->firstOrFail();
            
            // Validar que no sea la gestión activa
            if ($gestion->activo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la gestión activa. Active otra gestión primero.'
                ], 400);
            }

            $gestion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Gestión eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la gestión',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}