<?php

namespace App\Models\Horarios;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Sistema\Estado;
use App\Models\Horarios\Asistencia;
use App\Models\Usuarios\User;

class Justificacion extends Model
{
    use HasFactory;

    protected $table = 'justificacion';
    protected $primaryKey = 'id_justificacion';

    /**
     * Mapear timestamps personalizados
     */
    const CREATED_AT = 'fecha_justificacion';
    const UPDATED_AT = 'fecha_revision'; // El 'updated_at' será la fecha de revisión

    protected $fillable = [
        'id_asistencia',
        'id_estado',
        'motivo',
        'documento_adjunto',
        'respuesta_admin',
        'revisado_por_id_usuario',
    ];

    protected $casts = [
        'fecha_justificacion' => 'datetime',
        'fecha_revision' => 'datetime',
    ];

    // =========================================================================
    // RELACIONES
    // =========================================================================

    /**
     * La asistencia (ausencia) que se está justificando
     */
    public function asistencia()
    {
        return $this->belongsTo(Asistencia::class, 'id_asistencia', 'id_asistencia');
    }

    /**
     * El estado de la justificación (Pendiente, Aprobada, Rechazada)
     */
    public function estado()
    {
        return $this->belongsTo(Estado::class, 'id_estado', 'id_estado');
    }

    /**
     * El admin/coordinador que revisó esta solicitud
     */
    public function revisor()
    {
        // Asumiendo que 'revisado_por_id_usuario' es un FK a tu tabla 'users'
        return $this->belongsTo(User::class, 'revisado_por_id_usuario', 'id_usuario');
    }
}