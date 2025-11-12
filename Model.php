<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo User - Entidad principal de usuarios del sistema
 * 
 * Representa a un usuario autenticado en la plataforma con capacidades
 * de autenticación, autorización y gestión de perfil.
 * 
 * @package App\Models
 * @version 3.2.1
 * 
 * @property int $id Identificador único auto-incremental
 * @property string $name Nombre completo del usuario
 * @property string $email Dirección de email única
 * @property string $password Hash de contraseña (bcrypt)
 * @property \Carbon\Carbon|null $email_verified_at Fecha de verificación de email
 * @property string|null $remember_token Token para "recordar sesión"
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de última actualización
 * @property \Carbon\Carbon|null $deleted_at Fecha de eliminación suave
 * 
 * @property-read \App\Models\UserProfile|null $profile Relación con perfil de usuario
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Post> $posts Posts del usuario
 * @property-read \Illuminate\Database\Eloquent\Collection<\Laravel\Sanctum\PersonalAccessToken> $tokens Tokens de API
 * 
 * @method static \Database\Factories\UserFactory factory($count = null, $state = []) Generador de factories
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery() Iniciar nueva query
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery() Crear nuevo query
 * @method static \Illuminate\Database\Eloquent\Builder|User query() Iniciar query estándar
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value) Filtrar por fecha creación
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDeletedAt($value) Filtrar por fecha eliminación
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value) Filtrar por email
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value) Filtrar por verificación
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value) Filtrar por ID
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value) Filtrar por nombre
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value) Filtrar por contraseña
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value) Filtrar por token
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value) Filtrar por actualización
 * 
 * @author Tu Equipo <equipo@empresa.com>
 * @copyright 2025 Empresa XYZ
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     * 
     * Campos que pueden ser asignados masivamente
     * Seguridad: Solo incluir campos que el usuario puede modificar directamente
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * 
     * Atributos ocultos en respuestas JSON/API
     * Seguridad: Prevenir exposición de datos sensibles
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     * 
     * Conversiones automáticas de tipos de datos
     * Beneficios: Type safety y consistencia de datos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // Laravel 10+ - hashing automático
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación uno-a-uno con UserProfile
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * 
     * @example
     * $user = User::find(1);
     * $profile = $user->profile; // Acceder al perfil
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Relación uno-a-muchos con Post
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * 
     * @example
     * $user = User::find(1);
     * $posts = $user->posts; // Todos los posts del usuario
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Scope para usuarios verificados por email
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     * 
     * @example
     * $verifiedUsers = User::verified()->get();
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope para búsqueda por nombre o email
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search Término de búsqueda
     * @return \Illuminate\Database\Eloquent\Builder
     * 
     * @example
     * $users = User::search('john')->get();
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
    }

    /**
     * Verificar si el usuario tiene un perfil completo
     * 
     * @return bool
     * 
     * @example
     * if ($user->hasCompleteProfile()) {
     *     // Lógica para perfil completo
     * }
     */
    public function hasCompleteProfile(): bool
    {
        return $this->profile !== null && 
               $this->profile->is_complete === true;
    }

    /**
     * Obtener el nombre completo en formato capitalizado
     * 
     * @return string
     * 
     * @example
     * echo $user->getFormattedName(); // "John Doe"
     */
    public function getFormattedName(): string
    {
        return ucwords(strtolower($this->name));
    }

    /**
     * Marcar el email como verificado
     * 
     * @return bool
     * 
     * @example
     * $user->markEmailAsVerified();
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }
}