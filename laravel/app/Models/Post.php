<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Post",
    type: "object",
    title: "Post",
    description: "Post model",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "content", type: "string", example: "This is a sample post content"),
        new OA\Property(property: "user_id", type: "integer", example: 1),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
        new OA\Property(property: "deleted_at", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "author", ref: "#/components/schemas/User"),
        new OA\Property(property: "likers_count", type: "integer", example: 5),
        new OA\Property(
            property: "likers",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/User")
        )
    ]
)]
class Post extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'content',
    ];

    public function author() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function likers() {
        return $this->belongsToMany(User::class, 'likes')->withTimestamps();
    }
}
