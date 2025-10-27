<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Semestre extends Model
{
    use HasFactory;

    protected $table = 'semestre';
    protected $primaryKey = 'id_semestre';
    public $timestamps = false;

    protected $fillable = ['nombre'];
}
