<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_posts_relationship()
    {
        $user = User::factory()->create();
        $posts = Post::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->posts);
        $this->assertInstanceOf(Post::class, $user->posts->first());
    }

    public function test_user_has_liked_posts_relationship()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $user->likedPosts()->attach($post->id);

        $this->assertCount(1, $user->likedPosts);
        $this->assertEquals($post->id, $user->likedPosts->first()->id);
    }

    public function test_user_can_access_panel_when_approved()
    {
        $user = User::factory()->create(['is_approved' => true]);

        $this->assertTrue($user->canAccessPanel(new \Filament\Panel()));
    }

    public function test_user_cannot_access_panel_when_not_approved()
    {
        $user = User::factory()->create(['is_approved' => false]);

        $this->assertFalse($user->canAccessPanel(new \Filament\Panel()));
    }

    public function test_user_password_is_hidden_in_array()
    {
        $user = User::factory()->create();
        $userArray = $user->toArray();

        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertArrayNotHasKey('remember_token', $userArray);
    }

    public function test_user_casts_boolean_fields_correctly()
    {
        $user = User::factory()->create([
            'is_approved' => 1,
            'is_admin' => 0
        ]);

        $this->assertIsBool($user->is_approved);
        $this->assertIsBool($user->is_admin);
        $this->assertTrue($user->is_approved);
        $this->assertFalse($user->is_admin);
    }

    public function test_deleting_user_soft_deletes_posts()
    {
        $user = User::factory()->create();
        $posts = Post::factory()->count(2)->create(['user_id' => $user->id]);

        $user->delete();

        foreach ($posts as $post) {
            $this->assertSoftDeleted('posts', ['id' => $post->id]);
        }
    }

    public function test_restoring_user_restores_posts()
    {
        $user = User::factory()->create();
        $posts = Post::factory()->count(2)->create(['user_id' => $user->id]);

        $user->delete();

        $user->restore();

        foreach ($posts as $post) {
            $this->assertDatabaseHas('posts', [
                'id' => $post->id,
                'deleted_at' => null
            ]);
        }
    }

    public function test_restoring_user_does_not_restore_manually_deleted_posts()
    {
        $user = User::factory()->create();
        $post1 = Post::factory()->create(['user_id' => $user->id]);
        $post2 = Post::factory()->create(['user_id' => $user->id]);

        $post2->delete();

        sleep(1);

        $user->delete();

        $user->restore();

        $this->assertDatabaseHas('posts', ['id' => $post1->id, 'deleted_at' => null]);
        $this->assertSoftDeleted('posts', ['id' => $post2->id]);
    }

}
