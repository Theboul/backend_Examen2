<?php

namespace App\Models\Horarios;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Horarios\AsignacionDocente;
use App\Models\Horarios\HorarioClase;
use App\Models\Sistema\Estado;

class Asistencia extends Model
{
    use HasFactory;

    protected $table = 'asistencia';
    protected $primaryKey = 'id_asistencia';

    /**
     * Esta tabla no usa los timestamps 'created_at'/'updated_at'.
     * Maneja 'fecha_registro' y 'hora_registro' manualmente.
     */
    public $timestamps = false;

    protected $fillable = [
        'id_asignacion_docente',
        'id_horario_clase',
        'id_estado',
        'fecha_registro',
        'hora_registro',
        'tipo_registro', // 'BOTON_GPS', 'QR_VALIDADO', 'MANUAL_ADMIN'
        'observacion',
    ];

    protected $casts = [
        'fecha_registro' => 'date:Y-m-d',
        'hora_registro' => 'datetime:H:i:s', // Facilita el manejo como objeto
    ];

    // =========================================================================
    // RELACIONES
    // =========================================================================

    /**
     * La asignación que generó esta asistencia (Docente + MateriaGrupo)
     */
    public function asignacionDocente()
    {
        return $this->belongsTo(AsignacionDocente::class, 'id_asignacion_docente', 'id_asignacion_docente');
    }

    /**
     * El bloque de horario específico (Aula + Día + Bloque)
     */
    public function horarioClase()
    {
        return $this->belongsTo(HorarioClase::class, 'id_horario_clase', 'id_horario_clase');
    }

    /**
     * El estado de la asistencia (Presente, Tardanza, Ausente, etc.)
     */
    public function estado()
    {
        return $this->belongsTo(Estado::class, 'id_estado', 'id_estado');
    }
}