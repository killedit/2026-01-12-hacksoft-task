<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'content' => fake()->paragraph(rand(1, 3)),
        ];
    }

    /**
     * Create a post with short content.
     */
    public function short(): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => fake()->sentence(),
        ]);
    }

    /**
     * Create a post with long content.
     */
    public function long(): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => fake()->paragraphs(5, true),
        ]);
    }

    public function test_user_can_create_long_format_post()
    {
        $user = $this->signIn();
        $postData = Post::factory()->long()->make()->toArray();

        $this->postJson('/api/posts', $postData)->assertStatus(201);
    }
}