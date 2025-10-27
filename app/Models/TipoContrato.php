<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoContrato extends Model
{
    use HasFactory;

    protected $table = 'tipo_contrato';
    protected $primaryKey = 'id_tipo_contrato';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'hrs_minimas',
        'hrs_maximas',
        'fecha_creacion'
    ];
}
