<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_delete_others_post_detailed()
    {
        $user1 = User::factory()->approved()->create();
        $user2 = User::factory()->approved()->create();

        $post = Post::factory()->create(['user_id' => $user1->id]);

        $token2 = $user2->createToken('test-token')->plainTextToken;

        $response = $this->deleteJson("/api/posts/{$post->id}/delete", [], [
            'Authorization' => 'Bearer ' . $token2
        ]);

        $response->assertStatus(403)
                ->assertJson([
                    'message' => 'Unauthorized.'
                ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'deleted_at' => null
        ]);
    }

    public function test_user_can_delete_own_post_detailed()
    {
        $user = User::factory()->approved()->create();

        $post = Post::factory()->create(['user_id' => $user->id]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->deleteJson("/api/posts/{$post->id}/delete", [], [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Post is soft deleted.'
                ]);

        $this->assertSoftDeleted('posts', [
            'id' => $post->id
        ]);
    }
}
