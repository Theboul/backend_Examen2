<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\User;
use App\Models\PerfilUsuario;
use App\Models\Bitacora;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class DocenteController extends Controller
{
    /**
     * Listar todos los docentes con filtros opcionales
     */
    public function index(Request $request)
    {
        try {
            $query = Docente::with(['perfil', 'tipoContrato', 'usuario.rol']);

            // Filtro por estado (activo/inactivo)
            if ($request->has('activo')) {
                $query->where('activo', $request->boolean('activo'));
            }

            // Filtro por tipo de contrato
            if ($request->has('id_tipo_contrato')) {
                $query->where('id_tipo_contrato', $request->id_tipo_contrato);
            }

            // Filtro por nombre o CI
            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->whereHas('perfil', function ($q) use ($buscar) {
                    $q->where('nombres', 'ILIKE', "%{$buscar}%")
                      ->orWhere('apellidos', 'ILIKE', "%{$buscar}%")
                      ->orWhere('ci', 'ILIKE', "%{$buscar}%");
                });
            }

            // Incluir inactivos
            if (!$request->has('incluir_inactivos') || !$request->boolean('incluir_inactivos')) {
                $query->where('activo', true);
            }

            $docentes = $query->orderBy('cod_docente', 'desc')->get();

            // Agregar nombre completo a cada docente
            $docentes->transform(function ($docente) {
                $docente->nombre_completo = $docente->perfil?->nombre_completo;
                return $docente;
            });

            return response()->json([
                'success' => true,
                'data' => $docentes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener docentes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo docente
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                // Datos de usuario (id_rol se asigna automáticamente como "Docente")
                'usuario' => 'required|string|max:100|unique:users,usuario',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:6',
                
                // Datos de perfil
                'nombres' => 'required|string|max:100',
                'apellidos' => 'required|string|max:100',
                'ci' => 'required|string|max:20|unique:perfil_usuario,ci',
                'telefono' => 'nullable|string|max:20',
                'fecha_nacimiento' => 'nullable|date',
                'genero' => 'required|in:M,F',
                
                // Datos de docente
                'id_tipo_contrato' => 'required|integer|exists:tipo_contrato,id_tipo_contrato',
                'titulo' => 'required|string|max:150',
                'especialidad' => 'nullable|string|max:100',
                'grado_academico' => 'nullable|string|max:100',
                'fecha_ingreso' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Obtener el rol "Docente" automáticamente
            $rolDocente = Rol::where('nombre', 'ILIKE', 'Docente')
                ->where('activo', true)
                ->first();
            
            if (!$rolDocente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: No existe el rol "Docente" activo en el sistema. Por favor, créelo primero.'
                ], 422);
            }

            // 1. Crear usuario con rol de Docente
            $user = User::create([
                'id_rol' => $rolDocente->id_rol,
                'usuario' => $request->usuario,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // 2. Crear perfil de usuario
            $perfil = PerfilUsuario::create([
                'id_usuario' => $user->id_usuario,
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'ci' => $request->ci,
                'email' => $request->email,
                'telefono' => $request->telefono,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'genero' => $request->genero,
            ]);

            // 3. Crear docente con código autogenerado
            $codigoDocente = Docente::generarCodigoDocente();
            
            $docente = Docente::create([
                'cod_docente' => $codigoDocente,
                'id_usuario' => $user->id_usuario,
                'id_tipo_contrato' => $request->id_tipo_contrato,
                'titulo' => $request->titulo,
                'especialidad' => $request->especialidad,
                'grado_academico' => $request->grado_academico,
                'fecha_ingreso' => $request->fecha_ingreso ?? now()->toDateString(),
                'activo' => true,
            ]);

            // Registrar en bitácora
            Bitacora::registrar(
                'CREAR',
                "Docente creado: {$perfil->nombre_completo} - Código: {$codigoDocente}"
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Docente creado exitosamente',
                'data' => $docente->load(['perfil', 'tipoContrato'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el docente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un docente específico
     */
    public function show($id)
    {
        try {
            $docente = Docente::with(['perfil', 'tipoContrato', 'usuario.rol'])->findOrFail($id);
            $docente->nombre_completo = $docente->perfil?->nombre_completo;

            return response()->json([
                'success' => true,
                'data' => $docente
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Docente no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Actualizar un docente
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $docente = Docente::with('perfil', 'usuario')->findOrFail($id);

            $validator = Validator::make($request->all(), [
                // Datos de usuario
                'email' => [
                    'sometimes',
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($docente->id_usuario, 'id_usuario')
                ],
                'password' => 'nullable|string|min:6',
                
                // Datos de perfil
                'nombres' => 'sometimes|required|string|max:100',
                'apellidos' => 'sometimes|required|string|max:100',
                'ci' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('perfil_usuario', 'ci')->ignore($docente->perfil->id_perfil_usuario, 'id_perfil_usuario')
                ],
                'telefono' => 'nullable|string|max:20',
                'fecha_nacimiento' => 'nullable|date',
                'genero' => 'sometimes|required|in:M,F',
                
                // Datos de docente
                'id_tipo_contrato' => 'sometimes|required|integer|exists:tipo_contrato,id_tipo_contrato',
                'titulo' => 'sometimes|required|string|max:150',
                'especialidad' => 'nullable|string|max:100',
                'grado_academico' => 'nullable|string|max:100',
                'fecha_ingreso' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Actualizar usuario si se envía email o password
            if ($request->has('email') || $request->has('password')) {
                $userData = [];
                if ($request->has('email')) {
                    $userData['email'] = $request->email;
                }
                if ($request->filled('password')) {
                    $userData['password'] = Hash::make($request->password);
                }
                $docente->usuario->update($userData);
            }

            // Actualizar perfil de usuario
            if ($request->hasAny(['nombres', 'apellidos', 'ci', 'email', 'telefono', 'fecha_nacimiento', 'genero'])) {
                $docente->perfil->update($request->only([
                    'nombres', 'apellidos', 'ci', 'telefono', 'fecha_nacimiento', 'genero'
                ]));
                
                if ($request->has('email')) {
                    $docente->perfil->update(['email' => $request->email]);
                }
            }

            // Actualizar datos de docente
            $docente->update($request->only([
                'id_tipo_contrato',
                'titulo',
                'especialidad',
                'grado_academico',
                'fecha_ingreso',
            ]));

            // Registrar en bitácora
            Bitacora::registrar(
                'ACTUALIZAR',
                "Docente actualizado: {$docente->perfil->nombre_completo} - Código: {$docente->cod_docente}"
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Docente actualizado exitosamente',
                'data' => $docente->fresh(['perfil', 'tipoContrato'])
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el docente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Desactivar un docente (eliminación lógica)
     */
    public function destroy($id)
    {
        try {
            $docente = Docente::with('perfil')->findOrFail($id);

            // Verificar si puede ser desactivado
            if (!$docente->puedeDesactivarse()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede desactivar el docente porque tiene asignaciones activas'
                ], 422);
            }

            $docente->update(['activo' => false]);

            // Registrar en bitácora
            Bitacora::registrar(
                'DESACTIVAR',
                "Docente desactivado: {$docente->perfil->nombre_completo} - Código: {$docente->cod_docente}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Docente desactivado exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar el docente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reactivar un docente
     */
    public function reactivar($id)
    {
        try {
            $docente = Docente::with('perfil')->findOrFail($id);

            $docente->update(['activo' => true]);

            // Registrar en bitácora
            Bitacora::registrar(
                'REACTIVAR',
                "Docente reactivado: {$docente->perfil->nombre_completo} - Código: {$docente->cod_docente}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Docente reactivado exitosamente',
                'data' => $docente->load(['perfil', 'tipoContrato'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reactivar el docente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener docentes para select (solo activos)
     */
    public function getDocentesForSelect(Request $request)
    {
        try {
            $query = Docente::activos()
                ->with('perfil:id_usuario,nombres,apellidos')
                ->select('cod_docente', 'id_usuario', 'id_tipo_contrato');

            // Filtrar por tipo de contrato si se proporciona
            if ($request->has('id_tipo_contrato')) {
                $query->where('id_tipo_contrato', $request->id_tipo_contrato);
            }

            $docentes = $query->get()->map(function ($docente) {
                return [
                    'cod_docente' => $docente->cod_docente,
                    'nombre_completo' => $docente->perfil?->nombre_completo,
                    'id_tipo_contrato' => $docente->id_tipo_contrato,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $docentes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener docentes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
