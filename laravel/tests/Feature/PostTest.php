<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_post()
    {
        $user = User::factory()->create(['is_approved' => true]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->postJson('/api/posts', [
            'content' => 'This is a test post content'
        ], [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(201)
                ->assertJson([
                    'message' => 'Post created successfully.',
                    'post' => [
                        'content' => 'This is a test post content',
                        'user_id' => $user->id
                    ]
                ]);

        $this->assertDatabaseHas('posts', [
            'content' => 'This is a test post content',
            'user_id' => $user->id
        ]);
    }

    public function test_post_content_is_required()
    {
        $user = User::factory()->create(['is_approved' => true]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->postJson('/api/posts', [], [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['content']);
    }

    public function test_post_content_cannot_exceed_1000_characters()
    {
        $user = User::factory()->create(['is_approved' => true]);
        $token = $user->createToken('test-token')->plainTextToken;

        $longContent = str_repeat('a', 1001);

        $response = $this->postJson('/api/posts', [
            'content' => $longContent
        ], [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['content']);
    }

    public function test_authenticated_user_can_get_posts()
    {
        $user = User::factory()->create(['is_approved' => true]);
        $token = $user->createToken('test-token')->plainTextToken;

        $posts = Post::factory()->count(3)->create();

        $response = $this->getJson('/api/posts', [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'content',
                            'user_id',
                            'created_at',
                            'updated_at',
                            'author',
                            'likers_count'
                        ]
                    ],
                    'next_cursor',
                    'prev_cursor',
                    'per_page'
                ]);
    }

    public function test_authenticated_user_can_like_post()
    {
        $user = User::factory()->create(['is_approved' => true]);
        $token = $user->createToken('test-token')->plainTextToken;
        $post = Post::factory()->create();

        $response = $this->postJson("/api/posts/{$post->id}/like", [], [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Post liked',
                    'is_liked' => true,
                    'likes_count' => 1
                ]);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'post_id' => $post->id
        ]);
    }

    public function test_authenticated_user_can_unlike_post()
    {
        $user = User::factory()->create(['is_approved' => true]);
        $token = $user->createToken('test-token')->plainTextToken;
        $post = Post::factory()->create();

        $user->likedPosts()->attach($post->id);

        $response = $this->postJson("/api/posts/{$post->id}/like", [], [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Post unliked',
                    'is_liked' => false,
                    'likes_count' => 0
                ]);

        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'post_id' => $post->id
        ]);
    }

    public function test_user_can_delete_own_post()
    {
        $user = User::factory()->create(['is_approved' => true]);
        $token = $user->createToken('test-token')->plainTextToken;
        $post = Post::factory()->create(['user_id' => $user->id]);

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

    public function test_user_cannot_delete_others_post()
    {
        $user = User::factory()->create(['is_approved' => true]);
        $otherUser = User::factory()->create(['is_approved' => true]);
        $token = $user->createToken('test-token')->plainTextToken;
        $post = Post::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->deleteJson("/api/posts/{$post->id}/delete", [], [
            'Authorization' => 'Bearer ' . $token
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

    public function test_unauthenticated_user_cannot_access_posts()
    {
        $response = $this->getJson('/api/posts');
        $response->assertStatus(401);

        $response = $this->postJson('/api/posts', ['content' => 'test']);
        $response->assertStatus(401);

        $post = Post::factory()->create();
        $response = $this->postJson("/api/posts/{$post->id}/like");
        $response->assertStatus(401);

        $response = $this->deleteJson("/api/posts/{$post->id}/delete");
        $response->assertStatus(401);
    }
}
