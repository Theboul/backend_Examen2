<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class BloqueHorario extends Model
{
    use HasFactory;

    protected $table = 'bloque_horario';
    protected $primaryKey = 'id_bloque_horario';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'hr_inicio',
        'hr_fin',
        'minutos_duracion',
        'activo',
        'fecha_creacion',
        'fecha_modificacion'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
