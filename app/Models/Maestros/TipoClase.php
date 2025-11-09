<?php

namespace App\Models\Maestros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Horarios\HorarioClase;

class TipoClase extends Model
{
    use HasFactory;

    protected $table = 'tipo_clase';
    protected $primaryKey = 'id_tipo_clase';
    
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = null; // No tiene fecha_modificacion
    
    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // =========================================================================
    // RELACIONES
    // =========================================================================

    public function horariosClase()
    {
        return $this->hasMany(HorarioClase::class, 'id_tipo_clase');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
