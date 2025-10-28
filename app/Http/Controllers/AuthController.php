<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Docente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Iniciar sesión
     * Soporta login con:
     * - Email (para todos los usuarios)
     * - Código de docente (solo para docentes)
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => 'required|string', // Puede ser email o cod_docente
                'password' => 'required|string',
            ], [
                'username.required' => 'Ingrese su nombre de usuario o email',
                'password.required' => 'Ingrese su contraseña',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $username = $request->username;
            $password = $request->password;

            // Buscar usuario por email o código de docente
            $user = $this->buscarUsuario($username);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            // Verificar si la cuenta está activa
            if (!$user->activo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Su cuenta está inactiva. Contacte al administrador'
                ], 403);
            }

            // Verificar contraseña
            if (!Hash::check($password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            // Login exitoso - Actualizar último acceso
            $user->update([
                'ultimo_acceso' => now()
            ]);

            // Cargar relaciones
            $user->load('perfilUsuario', 'docente');

            // Verificar si es primer ingreso
            $primerIngreso = $user->primer_ingreso === null;

            if ($primerIngreso) {
                $user->update(['primer_ingreso' => now()]);
            }

            // Generar token (por ahora simulado, después implementarás Laravel Sanctum)
            $token = base64_encode($user->id_usuario . ':' . now()->timestamp);

            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión exitoso',
                'data' => [
                    'user' => [
                        'id_usuario' => $user->id_usuario,
                        'usuario' => $user->usuario,
                        'email' => $user->email,
                        'id_rol' => $user->id_rol,
                        'perfil' => $user->perfilUsuario,
                        'docente' => $user->docente,
                    ],
                    'token' => $token,
                    'primer_ingreso' => $primerIngreso,
                    'debe_cambiar_password' => $primerIngreso, // Forzar cambio en primer ingreso
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar usuario por email o código de docente
     */
    private function buscarUsuario($username)
    {
        // Intentar buscar por email primero
        $user = User::where('email', $username)->first();

        if ($user) {
            return $user;
        }

        // Si no se encuentra y el username es numérico, buscar por código de docente
        if (is_numeric($username)) {
            $docente = Docente::where('cod_docente', $username)
                ->where('activo', true)
                ->first();

            if ($docente) {
                return User::find($docente->id_usuario);
            }
        }

        return null;
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request)
    {
        // TODO: Implementar invalidación de token con Laravel Sanctum
        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente'
        ], 200);
    }

    /**
     * Cambiar contraseña en primer ingreso
     */
    public function cambiarPasswordPrimerIngreso(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_usuario' => 'required|integer|exists:users,id_usuario',
                'password_actual' => 'required|string',
                'password_nueva' => 'required|string|min:6|confirmed',
            ], [
                'password_nueva.confirmed' => 'Las contraseñas no coinciden',
                'password_nueva.min' => 'La contraseña debe tener al menos 6 caracteres',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::findOrFail($request->id_usuario);

            // Verificar contraseña actual
            if (!Hash::check($request->password_actual, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta'
                ], 401);
            }

            // Actualizar contraseña
            $user->update([
                'password' => Hash::make($request->password_nueva)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar la contraseña',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar/Desactivar cuenta de usuario (solo admin)
     */
    public function toggleActivoCuenta($idUsuario)
    {
        try {
            $user = User::findOrFail($idUsuario);

            $user->update(['activo' => !$user->activo]);

            return response()->json([
                'success' => true,
                'message' => $user->activo ? 'Cuenta activada exitosamente' : 'Cuenta desactivada exitosamente',
                'data' => [
                    'id_usuario' => $user->id_usuario,
                    'email' => $user->email,
                    'activo' => $user->activo
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado de cuenta',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
