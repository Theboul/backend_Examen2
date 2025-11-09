<?php

namespace App\Models\Horarios;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Horarios\AsignacionDocente;
use App\Models\Maestros\Aula;
use App\Models\Horarios\Dia;
use App\Models\Horarios\BloqueHorario;
use App\Models\Maestros\TipoClase;
use App\Models\Sistema\Estado;

class HorarioClase extends Model
{
    use HasFactory;

    protected $table = 'horario_clase';
    protected $primaryKey = 'id_horario_clase';
    
    // Solo usa created_at ya que la tabla solo tiene fecha_creacion
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = null; // No tiene fecha_modificacion
    
    public $timestamps = true;

    protected $fillable = [
        'id_asignacion_docente', // FK a AsignacionDocente (CU16)
        'id_aula',
        'id_dia',
        'id_bloque_horario',
        'id_tipo_clase',
        'activo',
        'id_estado', // CU17: FK a tabla estado
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
    
    // =========================================================================
    // SCOPES
    // =========================================================================
    
    /**
     * Scope para obtener solo horarios publicados (CU17)
     */
    public function scopePublicados($query)
    {
        return $query->whereHas('estado', function($q) {
            $q->where('nombre', 'PUBLICADA');
        })->where('activo', true);
    }
    
    /**
     * Scope para obtener horarios aprobados pendientes de publicación
     */
    public function scopeAprobados($query)
    {
        return $query->whereHas('estado', function($q) {
            $q->where('nombre', 'APROBADA');
        })->where('activo', true);
    }
    
    /**
     * Scope para obtener horarios en borrador
     */
    public function scopeBorradores($query)
    {
        return $query->whereHas('estado', function($q) {
            $q->where('nombre', 'BORRADOR');
        })->where('activo', true);
    }

    // =========================================================================
    // RELACIONES
    // =========================================================================

    public function asignacionDocente()
    {
        // El id_asignacion_docente es la relación clave que lleva a MateriaGrupo, Docente, y Gestión.
        return $this->belongsTo(AsignacionDocente::class, 'id_asignacion_docente');
    }

    public function aula()
    {
        return $this->belongsTo(Aula::class, 'id_aula');
    }

    public function dia()
    {
        return $this->belongsTo(Dia::class, 'id_dia');
    }

    public function bloqueHorario()
    {
        return $this->belongsTo(BloqueHorario::class, 'id_bloque_horario');
    }

    public function tipoClase()
    {
        // Asumiendo que tienes un modelo TipoClase
        return $this->belongsTo(TipoClase::class, 'id_tipo_clase'); 
    }
    
    public function estado()
    {
        // CU17: Relación con tabla estado
        return $this->belongsTo(Estado::class, 'id_estado');
    }
    
    // =========================================================================
    // SCOPES Y LÓGICA DE NEGOCIO
    // =========================================================================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
