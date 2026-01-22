<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        $stats = $user->posts()
            ->leftJoin('likes', 'posts.id', '=', 'likes.post_id')
            ->selectRaw('COUNT(DISTINCT posts.id) as total_posts')
            ->selectRaw('COUNT(likes.user_id) as total_likes')
            ->first();

        return response()->json([
            'user' => $user,
            'stats' => [
                'total_posts' => (int) $stats->total_posts,
                'total_likes' => (int) $stats->total_likes,
            ]
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'profile_picture' => 'sometimes|image|max:2048',
        ]);

        if ($request->hasFile('profile_picture')) {
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            $path = $request->file('profile_picture')->store('profile-pictures', 'public');
            $validated['profile_picture'] = $path;
        }
// dd(
    // $user,
    // $validated
// );
        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->fresh(),
        ]);
    }
}
