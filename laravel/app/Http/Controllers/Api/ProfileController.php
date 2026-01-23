<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class ProfileController extends Controller
{
    #[OA\Get(
        path: "/me",
        summary: "Get current user profile",
        description: "Retrieve the currently authenticated user's profile with statistics",
        security: [["bearerAuth" => []]],
        tags: ["Profile"]
    )]
    #[OA\Response(
        response: 200,
        description: "User profile with statistics",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "user", ref: "#/components/schemas/User"),
                new OA\Property(
                    property: "stats",
                    type: "object",
                    properties: [
                        new OA\Property(property: "total_posts", type: "integer", description: "Total number of posts by the user", example: 5),
                        new OA\Property(property: "total_likes", type: "integer", description: "Total number of likes received on user's posts", example: 42)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Unauthorized")]
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

    #[OA\Post(
        path: "/me",
        summary: "Update current user profile",
        description: "Update profile information (name, description, and/or profile picture). Uses POST with _method=PATCH due to file upload requirements.",
        security: [["bearerAuth" => []]],
        tags: ["Profile"]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: "_method", type: "string", enum: ["PATCH"], example: "PATCH", description: "HTTP method override for file uploads"),
                    new OA\Property(property: "name", type: "string", maxLength: 255, nullable: true, example: "User's changed the name."),
                    new OA\Property(property: "description", type: "string", nullable: true, example: "User's changed the description."),
                    new OA\Property(property: "profile_picture", type: "string", format: "binary", nullable: true, description: "Profile picture image file (max 2MB)")
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Profile updated successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "message", type: "string", example: "Profile updated successfully"),
                new OA\Property(property: "user", ref: "#/components/schemas/User")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Unauthorized")]
    #[OA\Response(response: 422, description: "Validation error")]
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
