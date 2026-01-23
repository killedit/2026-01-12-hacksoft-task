<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create an authenticated user and return the token.
     */
    protected function createAuthenticatedUser(array $attributes = []): array
    {
        $user = User::factory()->approved()->create($attributes);
        $token = $user->createToken('test-token')->plainTextToken;
        
        return [
            'user' => $user,
            'token' => $token,
            'headers' => ['Authorization' => 'Bearer ' . $token]
        ];
    }

    /**
     * Create an admin user and return the token.
     */
    protected function createAuthenticatedAdmin(array $attributes = []): array
    {
        $user = User::factory()->admin()->create($attributes);
        $token = $user->createToken('test-token')->plainTextToken;
        
        return [
            'user' => $user,
            'token' => $token,
            'headers' => ['Authorization' => 'Bearer ' . $token]
        ];
    }
}
