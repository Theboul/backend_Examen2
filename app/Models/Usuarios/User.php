<?php

namespace App\Models\Usuarios;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'users';
    protected $primaryKey = 'id_usuario';
    
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id_rol',
        'usuario',
        'email',
        'password',
        'activo',
        'ultimo_acceso',
        'primer_ingreso',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'primer_ingreso' => 'datetime',
            'fecha_creacion' => 'datetime',
            'fecha_modificacion' => 'datetime',
            'ultimo_acceso' => 'datetime',
        ];
    }

    // Relaciones
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol');
    }

    public function perfil()
    {
        return $this->hasOne(PerfilUsuario::class, 'id_usuario');
    }

    // Alias para compatibilidad (deprecado, usar perfil())
    public function perfilUsuario()
    {
        return $this->perfil();
    }

    public function docente()
    {
        return $this->hasOne(Docente::class, 'id_usuario');
    }
}
