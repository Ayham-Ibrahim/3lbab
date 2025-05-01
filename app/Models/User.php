<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'roles'
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
        ];
    }

    protected $appends = [
        'role'
    ];

    public function getRoleAttribute(): string
    {
        return $this->roles->pluck('name')->implode(', ');
    }

    /**
     * Get the store associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function store(): HasOne
    {
        return $this->hasOne(Store::class, 'manager_id');
    }

    /**
     * Get all of the favourites for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function favourites(): HasMany
    {
        return $this->hasMany(Favourite::class, 'user_id', 'id');
    }

    /**
     * The favouriteProducts that belong to the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function favouriteProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favourites', 'user_id', 'product_id');
    }
}
