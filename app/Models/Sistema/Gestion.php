<?php

namespace App\Models\Sistema;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gestion extends Model
{
    use HasFactory;

    protected $table = 'gestion';
    protected $primaryKey = 'id_gestion';
    
    public $timestamps = false;
    
    protected $fillable = [
        'anio',
        'semestre',
        'fecha_inicio',
        'fecha_fin',
        'activo'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean'
    ];

    /**
     * Obtener la gestión activa actual
     */
    public static function getActiva()
    {
        return self::where('activo', true)->first();
    }

    /**
     * Activar esta gestión (y desactivar las demás)
     */
    public function activar()
    {
        \DB::transaction(function () {
            self::where('activo', true)->update(['activo' => false]);
            $this->update(['activo' => true]);
        });
    }
    
    /**
     * Scope para gestiones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para gestiones inactivas
     */
    public function scopeInactivas($query)
    {
        return $query->where('activo', false);
    }

    /**
     * Verificar si la gestión puede ser desactivada
     */
    public function puedeDesactivarse(): bool
    {
        // Una gestión activa no puede desactivarse directamente
        // Primero debe activarse otra gestión
        if ($this->activo) {
            return false;
        }
        
        // Las gestiones inactivas siempre pueden "desactivarse" (ya lo están)
        return true;
    }
}