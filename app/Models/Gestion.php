<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gestion extends Model
{
    use HasFactory;

    protected $table = 'gestion';
    
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
            // Desactivar todas las gestiones
            self::where('activo', true)->update(['activo' => false]);
            // Activar esta gestión
            $this->update(['activo' => true]);
        });
    }
}