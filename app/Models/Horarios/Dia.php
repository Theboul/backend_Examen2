<?php

namespace App\Models\Horarios;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Dia extends Model
{
    use HasFactory;

    protected $table = 'dia';
    protected $primaryKey = 'id_dia';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'abreviatura'
    ];
}
