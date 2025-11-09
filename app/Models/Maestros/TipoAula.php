<?php

namespace App\Models\Maestros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoAula extends Model
{
    use HasFactory;

    protected $table = 'tipo_aula';
    protected $primaryKey = 'id_tipo_aula';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
        'fecha_creacion'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
