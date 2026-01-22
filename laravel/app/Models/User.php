<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_picture',
        'description',
        'is_approved',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pivot'
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
            'is_admin' => 'boolean',
            'is_approved' => 'boolean',
        ];
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return $this->is_approved === true;
    }

    public function posts() {
        return $this->hasMany(Post::class);
    }

    public function likedPosts() {
        return $this->belongsToMany(Post::class, 'likes');
    }

    protected static function booted()
    {
        static::deleting(function (User $user) {
            if ($user->isForceDeleting()) {
                $user->posts()->forceDelete();
            } else {
                $user->posts()->delete();
            }
        });

        static::restoring(function (User $user) {
            $user->posts()->onlyTrashed()->restore();
        });
    }

}
