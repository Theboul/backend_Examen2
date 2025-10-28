<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Middleware para verificar que el usuario tenga uno de los roles permitidos
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles - Nombres de roles permitidos (ej: 'Administrador', 'Coordinador')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Por ahora, como no tenemos autenticación real, validamos que venga el token
        $token = $request->header('Authorization');
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Token requerido.'
            ], 401);
        }

        // Decodificar el token temporal (base64)
        $token = str_replace('Bearer ', '', $token);
        $decoded = base64_decode($token);
        
        if (!$decoded) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido'
            ], 401);
        }

        // Extraer id_usuario del token (formato: "id_usuario:timestamp")
        $parts = explode(':', $decoded);
        if (count($parts) !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'Token con formato inválido'
            ], 401);
        }

        $idUsuario = $parts[0];

        // Buscar el usuario con su rol
        $user = \App\Models\User::with('rol')->find($idUsuario);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 401);
        }

        // Verificar si la cuenta está activa
        if (!$user->activo) {
            return response()->json([
                'success' => false,
                'message' => 'Cuenta inactiva'
            ], 403);
        }

        // Verificar si el rol del usuario está en los roles permitidos
        $nombreRol = $user->rol->nombre;
        
        // Comparación case-insensitive
        $rolesPermitidos = array_map('strtolower', $roles);
        $rolUsuario = strtolower($nombreRol);

        if (!in_array($rolUsuario, $rolesPermitidos)) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para acceder a este recurso',
                'rol_requerido' => $roles,
                'rol_usuario' => $nombreRol
            ], 403);
        }

        // Agregar el usuario al request para usarlo en los controladores
        $request->attributes->set('user', $user);

        return $next($request);
    }
}
