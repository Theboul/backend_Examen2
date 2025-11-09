<?php

namespace App\Models\Maestros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Maestros\Carrera;
use App\Models\Maestros\Semestre;
use App\Models\Maestros\Grupo;
use App\Models\Maestros\MateriaGrupo;

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

    // Relaciones hacia otros módulos
    public function grupos()
    { return $this->hasMany(Grupo::class, 'id_materia'); }
    public function materiaGrupos()
    { return $this->hasMany(MateriaGrupo::class, 'id_materia'); }

    // Scopes
    public function scopeActivas($q)
    { return $q->where('activo', true); }
    public function scopeInactivas($q)
    { return $q->where('activo', false); }

    // Verifica si puede ser desactivada
    public function puedeDesactivarse(): bool
    {
        $tieneGruposActivos = $this->grupos()->where('activo', true)->exists();
        $tieneMgActivos     = $this->materiaGrupos()->where('activo', true)->exists();
        return !$tieneGruposActivos && !$tieneMgActivos;
    }
}
