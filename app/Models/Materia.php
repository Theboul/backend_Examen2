<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Materia extends Model
{
    use HasFactory;

    protected $table = 'materia';
    protected $primaryKey = 'id_materia';
    public $timestamps = false;

    protected $fillable = [
        'id_semestre',
        'id_carrera',
        'nombre',
        'sigla',
        'creditos',
        'carga_horaria_semestral',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Relaciones principales
    public function carrera()
    { return $this->belongsTo(Carrera::class, 'id_carrera'); }
    public function semestre()
    { return $this->belongsTo(Semestre::class, 'id_semestre'); }

    // TODO: Descomentar cuando existan estos modelos
    // Relaciones hacia otros módulos
    // public function grupos()
    // { return $this->hasMany(Grupo::class, 'id_materia'); }
    // public function materiaGrupos()
    // { return $this->hasMany(MateriaGrupo::class, 'id_materia'); }

    // Scopes
    public function scopeActivas($q)
    { return $q->where('activo', true); }
    public function scopeInactivas($q)
    { return $q->where('activo', false); }

    // Verifica si puede ser desactivada
    public function puedeDesactivarse(): bool
    {
        // TODO: Descomentar cuando existan los modelos Grupo y MateriaGrupo
        // $tieneGruposActivos = $this->grupos()->where('activo', true)->exists();
        // $tieneMgActivos     = $this->materiaGrupos()->where('activo', true)->exists();
        // return !$tieneGruposActivos && !$tieneMgActivos;
        
        // Por ahora permitir desactivar siempre
        return true;
    }
}
