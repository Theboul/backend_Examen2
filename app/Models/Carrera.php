<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Carrera extends Model
{
    use HasFactory;

    protected $table = 'carrera';
    protected $primaryKey = 'id_carrera';

    protected $fillable = [
        'nombre',
        'codigo',
        'duracion_anios',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'duracion_anios' => 'integer'
    ];

    /**
     * Scope para carreras activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para incluir todas las carreras (activas e inactivas)
     */
    public function scopeWithInactive($query)
    {
        return $query; // Sin filtro, incluye todas
    }

    /**
     * Relación con materias (solo activas)
     */
    public function materias()
    {
        return $this->hasMany(Materia::class, 'id_carrera')->where('activo', true);
    }

    /**
     * Relación con materias (incluyendo inactivas)
     */
    public function todasLasMaterias()
    {
        return $this->hasMany(Materia::class, 'id_carrera');
    }

    /**
     * Verificar si la carrera puede ser desactivada
     */
    public function puedeDesactivarse(): bool
    {
        return !$this->todasLasMaterias()->where('activo', true)->exists();
    }
}
