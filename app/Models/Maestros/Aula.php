<?php

namespace App\Models\Maestros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Maestros\TipoAula;

class Aula extends Model
{
    use HasFactory;

    protected $table = 'aula';
    protected $primaryKey = 'id_aula';
    
    // Usar nombres personalizados para timestamps
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'id_tipo_aula',
        'nombre',
        'capacidad',
        'piso',
        'mantenimiento',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'mantenimiento' => 'boolean',
        'capacidad' => 'integer',
        'piso' => 'integer',
        'fecha_creacion' => 'datetime',
        'fecha_modificacion' => 'datetime',
    ];

    /**
     * Relación con TipoAula
     */
    public function tipoAula()
    {
        return $this->belongsTo(TipoAula::class, 'id_tipo_aula');
    }

    /**
     * Scope para aulas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para incluir todas las aulas (activas e inactivas)
     */
    public function scopeWithInactive($query)
    {
        return $query;
    }

    /**
     * Scope para aulas disponibles (activas y sin mantenimiento)
     */
    public function scopeDisponibles($query)
    {
        return $query->where('activo', true)->where('mantenimiento', false);
    }

    /**
     * Scope para aulas en mantenimiento
     */
    public function scopeEnMantenimiento($query)
    {
        return $query->where('mantenimiento', true);
    }

    /**
     * Verificar si el aula puede ser desactivada
     */
    public function puedeDesactivarse(): bool
    {
        // Por ahora permitir desactivar siempre
        // TODO: Verificar si tiene horarios asignados cuando exista ese módulo
        return true;
    }
}
