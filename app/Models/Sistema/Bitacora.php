<?php

namespace App\Models\Sistema;

use Illuminate\Database\Eloquent\Model;
use App\Models\Usuarios\User;

class Bitacora extends Model
{
    protected $table = 'bitacora';
    protected $primaryKey = 'id_bitacora';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'accion',
        'descripcion',
        'ip',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    // Relación con Usuario
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Registrar acción en bitácora
     * 
     * @param string $accion Acción realizada (CREAR, ACTUALIZAR, ELIMINAR, etc.)
     * @param string $descripcion Descripción detallada de la acción
     * @param int|null $idUsuario ID del usuario (opcional, usa auth()->id() por defecto)
     * @return Bitacora|null
     */
    public static function registrar(string $accion, string $descripcion, $idUsuario = null)
    {
        try {
            return self::create([
                'id_usuario' => $idUsuario ?? auth()->id(),
                'accion' => strtoupper($accion),
                'descripcion' => $descripcion,
                'ip' => request()->ip(),
                'fecha' => now(),
            ]);
        } catch (\Exception $e) {
            // No lanzar error si falla bitácora, solo registrar en logs
            \Log::error('Error al registrar en bitácora: ' . $e->getMessage());
            return null;
        }
    }
}
