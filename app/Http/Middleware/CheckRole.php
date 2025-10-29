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
        // Obtener usuario autenticado por Sanctum
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Token requerido o expirado.'
            ], 401);
        }

        // Cargar rol si no está cargado
        if (!$user->relationLoaded('rol')) {
            $user->load('rol');
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

        return $next($request);
    }
}
