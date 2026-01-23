<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;
use OpenApi\Attributes as OA;

class PostController extends Controller
{
    #[OA\Get(
        path: "/posts",
        summary: "Get feed of posts",
        description: "Retrieve paginated list of posts with author and likes information",
        security: [["bearerAuth" => []]],
        tags: ["Posts"]
    )]
    #[OA\Parameter(
        name: "cursor",
        description: "Cursor for pagination",
        in: "query",
        required: false,
        schema: new OA\Schema(type: "string")
    )]
    #[OA\Response(
        response: 200,
        description: "List of posts with pagination",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Post")
                ),
                new OA\Property(property: "next_cursor", type: "string", nullable: true),
                new OA\Property(property: "prev_cursor", type: "string", nullable: true),
                new OA\Property(property: "per_page", type: "integer", example: 20),
                new OA\Property(property: "next_page_url", type: "string", nullable: true),
                new OA\Property(property: "prev_page_url", type: "string", nullable: true)
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Unauthorized")]
    public function index()
    {
        $posts = Post::with(['author', 'likers'])
            ->withCount('likers')
            ->latest()
            // ->get();
            ->cursorPaginate(20);

        return response()->json($posts);
    }

    #[OA\Post(
        path: "/posts",
        summary: "Create a new post",
        description: "Create a new post with content",
        security: [["bearerAuth" => []]],
        tags: ["Posts"]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["content"],
            properties: [
                new OA\Property(property: "content", type: "string", maxLength: 1000, example: "This is my new post content")
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Post created successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "message", type: "string", example: "Post created successfully."),
                new OA\Property(property: "post", ref: "#/components/schemas/Post")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Unauthorized")]
    #[OA\Response(response: 422, description: "Validation error")]
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

    #[OA\Post(
        path: "/posts/{post}/like",
        summary: "Toggle like on a post",
        description: "Like or unlike a post",
        security: [["bearerAuth" => []]],
        tags: ["Posts"]
    )]
    #[OA\Parameter(
        name: "post",
        description: "Post ID",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "Like status toggled",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "message", type: "string", example: "Post liked"),
                new OA\Property(property: "is_liked", type: "boolean", example: true),
                new OA\Property(property: "likes_count", type: "integer", example: 5)
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Unauthorized")]
    #[OA\Response(response: 404, description: "Post not found")]
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

    #[OA\Delete(
        path: "/posts/{post}/delete",
        summary: "Delete a post (soft delete)",
        description: "Soft delete a post. Only the post author can delete their own posts.",
        security: [["bearerAuth" => []]],
        tags: ["Posts"]
    )]
    #[OA\Parameter(
        name: "post",
        description: "Post ID",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "Post soft deleted successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "message", type: "string", example: "Post is soft deleted.")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Unauthorized")]
    #[OA\Response(response: 403, description: "User is not the post author")]
    #[OA\Response(response: 404, description: "Post not found")]
    public function destroy(Post $post)
    {
        if (auth()->id() !== $post->user_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $post->delete();

        return response()->json(['message' => 'Post is soft deleted.']);
    }
}
