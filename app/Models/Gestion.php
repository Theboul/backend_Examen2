<?php

namespace App\Models;

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
}