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
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "User",
    type: "object",
    title: "User",
    description: "User model",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "User"),
        new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com"),
        new OA\Property(property: "email_verified_at", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "profile_picture", type: "string", nullable: true, example: "profile-pictures/image123.jpg"),
        new OA\Property(property: "description", type: "string", nullable: true, example: "Just a user."),
        new OA\Property(property: "is_approved", type: "boolean", example: true),
        new OA\Property(property: "is_admin", type: "boolean", example: false),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
        new OA\Property(property: "deleted_at", type: "string", format: "date-time", nullable: true)
    ]
)]
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

        # Only restore posts that were deleted with the user. Not earlier.
        static::restoring(function (User $user) {
            $user->posts()
                ->onlyTrashed()
                ->where('deleted_at', '>=', $user->deleted_at)
                ->restore();
        });
    }

}
