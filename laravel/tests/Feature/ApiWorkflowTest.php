<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_user_workflow()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'description' => 'Test user description'
        ]);

        $response->assertStatus(201);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(403);

        $user = User::where('email', 'test@example.com')->first();
        $user->update(['is_approved' => true]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);
        $token = $response->json('token');

        $response = $this->getJson('/api/me', [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'user' => [
                        'name' => 'Test User',
                        'email' => 'test@example.com'
                    ],
                    'stats' => [
                        'total_posts' => 0,
                        'total_likes' => 0
                    ]
                ]);


        $response = $this->postJson('/api/me', [
            '_method' => 'PATCH',
            'name' => 'Updated Test User',
            'description' => 'Updated description'
        ], [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200);

        $response = $this->postJson('/api/posts', [
            'content' => 'This is my first post!'
        ], [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(201);
        $postId = $response->json('post.id');

        $response = $this->getJson('/api/posts', [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200)
                ->assertJsonCount(1, 'data');

        $response = $this->postJson("/api/posts/{$postId}/like", [], [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'is_liked' => true,
                    'likes_count' => 1
                ]);

        $response = $this->getJson('/api/me', [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'stats' => [
                        'total_posts' => 1,
                        'total_likes' => 1
                    ]
                ]);

        $response = $this->deleteJson("/api/posts/{$postId}/delete", [], [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200);

        $response = $this->postJson('/api/logout', [], [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200);
    }
}
