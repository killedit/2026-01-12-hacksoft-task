<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_authenticated_user_can_get_profile()
    {
        $user = User::factory()->create(['is_approved' => true]);
        $token = $user->createToken('test-token')->plainTextToken;

        $posts = Post::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/me', [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'profile_picture',
                        'description'
                    ],
                    'stats' => [
                        'total_posts',
                        'total_likes'
                    ]
                ])
                ->assertJson([
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email
                    ],
                    'stats' => [
                        'total_posts' => 3,
                        'total_likes' => 0
                    ]
                ]);
    }

    public function test_unauthenticated_user_cannot_get_profile()
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_update_profile()
    {
        $user = User::factory()->create(['is_approved' => true]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->postJson('/api/me', [
            '_method' => 'PATCH',
            'name' => 'Updated Name',
            'description' => 'Updated description'
        ], [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Profile updated successfully',
                    'user' => [
                        'name' => 'Updated Name',
                        'description' => 'Updated description'
                    ]
                ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'description' => 'Updated description'
        ]);
    }

    public function test_authenticated_user_can_update_profile_with_image()
    {
        if (!extension_loaded('gd') || !function_exists('imagejpeg')) {
            $this->markTestSkipped('GD library or imagejpeg function missing.');
            return;
        }

        $user = User::factory()->create(['is_approved' => true]);
        $token = $user->createToken('test-token')->plainTextToken;

        $file = UploadedFile::fake()->image('profile.jpg');

        $response = $this->post('/api/me', [
            '_method' => 'PATCH',
            'profile_picture' => $file
        ], [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ]);

        $response->assertStatus(200);
    }

    public function test_profile_update_validates_image_size()
    {
        $user = User::factory()->create(['is_approved' => true]);
        $token = $user->createToken('test-token')->plainTextToken;

        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not available');
        }

        $file = UploadedFile::fake()->create('large.jpg', 3000);

        $response = $this->postJson('/api/me', [
            '_method' => 'PATCH',
            'profile_picture' => $file
        ], [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['profile_picture']);
    }

    public function test_unauthenticated_user_cannot_update_profile()
    {
        $response = $this->postJson('/api/me', [
            '_method' => 'PATCH',
            'name' => 'Updated Name'
        ]);

        $response->assertStatus(401);
    }
}
