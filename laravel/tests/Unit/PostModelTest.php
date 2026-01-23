<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_belongs_to_author()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $post->author);
        $this->assertEquals($user->id, $post->author->id);
    }

    public function test_post_has_likers_relationship()
    {
        $post = Post::factory()->create();
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $post->likers()->attach($user->id);
        }

        $this->assertCount(3, $post->likers);
        $this->assertInstanceOf(User::class, $post->likers->first());
    }

    public function test_post_fillable_attributes()
    {
        $post = new Post();
        $fillable = $post->getFillable();

        $this->assertContains('user_id', $fillable);
        $this->assertContains('content', $fillable);
    }

    public function test_post_uses_soft_deletes()
    {
        $post = Post::factory()->create();
        $postId = $post->id;

        $post->delete();

        $this->assertSoftDeleted('posts', ['id' => $postId]);

        $this->assertNull(Post::find($postId));

        $this->assertNotNull(Post::withTrashed()->find($postId));
    }
}
