<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
