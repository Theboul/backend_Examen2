<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Docente extends Model
{
    use HasFactory;

    protected $table = 'docente';
    protected $primaryKey = 'cod_docente';
    public $incrementing = false; // cod_docente no es autoincremental
    protected $keyType = 'integer'; // Es de tipo entero
    
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'cod_docente', // Agregado para poder asignarlo manualmente
        'id_usuario',
        'id_tipo_contrato',
        'titulo',
        'especialidad',
        'grado_academico',
        'activo',
        'fecha_ingreso',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_ingreso' => 'date',
        'fecha_creacion' => 'datetime',
        'fecha_modificacion' => 'datetime',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function perfilUsuario()
    {
        // Acceder al perfil a través de la relación con usuario
        return $this->usuario()->first()?->perfilUsuario();
    }

    // Relación más eficiente usando hasManyThrough inverso
    public function perfil()
    {
        return $this->belongsTo(PerfilUsuario::class, 'id_usuario', 'id_usuario');
    }

    public function tipoContrato()
    {
        return $this->belongsTo(TipoContrato::class, 'id_tipo_contrato');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeInactivos($query)
    {
        return $query->where('activo', false);
    }

    // Verificar si el docente puede ser desactivado
    public function puedeDesactivarse(): bool
    {
        // TODO: Verificar si tiene asignaciones activas de horarios
        // Por ahora permitir desactivar siempre
        return true;
    }

    // Accessor para obtener nombre completo del docente
    public function getNombreCompletoAttribute(): ?string
    {
        return $this->perfil?->nombre_completo;
    }

    // Generar el siguiente código de docente automáticamente
    public static function generarCodigoDocente(): int
    {
        // Obtener el código más alto actual
        $ultimoCodigo = self::max('cod_docente') ?? 0;
        
        // Generar el siguiente código (mínimo 1000 para tener 4 dígitos)
        $nuevoCodigo = max($ultimoCodigo + 1, 1000);
        
        return $nuevoCodigo;
    }
}
