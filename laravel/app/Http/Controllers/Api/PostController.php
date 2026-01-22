<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['author', 'likers'])
            ->withCount('likers')
            ->latest()
            // ->get();
            ->cursorPaginate(20);

        return response()->json($posts);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'profile_picture' => 'nullable|string',
        ]);

        $request->user()->update($data);

        return response()->json(['message' => 'Profile updated successfully']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $post = $request->user()->posts()->create($validated);

        return response()->json([
            'message' => 'Post created successfully.',
            'post' => $post->load('author'),
        ], 201);
    }

    public function toggleLike(Post $post)
    {
        $user = auth()->user();

        $status = $user->likedPosts()->toggle($post->id);

        $wasLiked = count($status['attached']) > 0;

        return response()->json([
            'message' => $wasLiked ? 'Post liked' : 'Post unliked',
            'is_liked' => $wasLiked,
            'likes_count' => $post->likers()->count(),
        ]);
    }

    public function destroy(Post $post)
    {
        if (auth()->id() !== $post->user_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $post->delete();

        return response()->json(['message' => 'Post is soft deleted.']);
    }

    // public function restore($id)
    // {
    //     $post = Post::onlyTrashed()->findOrFail($id);

    //     if (auth()->id() !== $post->user_id) {
    //         return response()->json(['message' => 'Unauthorized'], 403);
    //     }

    //     $post->restore();
    //     return response()->json(['message' => 'Post restored']);
    // }

}
