<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{

    protected $table = 'bitacora';
    protected $primaryKey = 'id_bitacora';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'accion',
        'fecha',
        'descripcion',
        'ip',
    ];

    public function Usuario()
    {
        return $this->belongsTo(Users::class, 'id_usuario');
    }
}
