<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: "HackSoft API",
    version: "1.0.0",
    description: "JSON-based API for social media application"
)]
#[OA\Server(
    url: "http://127.0.0.1:8009/api",
    description: "Local development server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    description: "Enter your Bearer token in the format: Bearer {your_token_here}"
)]
#[OA\Tag(
    name: "Authentication",
    description: "Authentication endpoints"
)]
#[OA\Tag(
    name: "Profile",
    description: "User profile endpoints"
)]
#[OA\Tag(
    name: "Posts",
    description: "Post management endpoints"
)]
class AuthController extends Controller
{
    #[OA\Post(
        path: "/login",
        summary: "User login",
        description: "Authenticate user with email and password",
        tags: ["Authentication"]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email", "password"],
            properties: [
                new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com"),
                new OA\Property(property: "password", type: "string", format: "password", example: "user123")
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Login successful",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "token", type: "string", description: "Bearer token for API authentication", example: "1|abcdef123456..."),
                new OA\Property(property: "token_type", type: "string", example: "Bearer"),
                new OA\Property(property: "user", ref: "#/components/schemas/User")
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Invalid credentials",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "message", type: "string", example: "Invalid credentials")
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: "User awaiting approval",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "message", type: "string", example: "User is awaiting approval.")
            ]
        )
    )]
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        /**
         * @todo Rethink and retest!
         *
         * Auth::logout() is does nothing!? Approval != authentication. User isn’t authenticated yet and approval checks shouldn’t live inside login. It should be in middleware or state check.
         *
         * 1. Guard-level check
         * if (!Hash::check(...)) {
         *     return 401;
         * }
         *
         * if (!$user->is_approved) {
         *     return response()->json([
         *         'message' => 'Account not active'
         *     ], 423);
         * }
         *
         * Information leakage consideration.
         * No Auth::logout(). No side effects.
         *
         * 2.Middleware-level check
         * Route::middleware(['auth:sanctum', 'approved'])->group(...)
         *
         * class EnsureUserIsApproved
         * {
         *     public function handle($request, Closure $next)
         *     {
         *         if (!$request->user()->is_approved) {
         *             return response()->json(['message' => 'Account not approved'], 403);
         *         }
         *         return $next($request);
         *     }
         * }
         */
        if (!$user->is_approved) {
            Auth::logout();

            return response()->json([
                'message' => 'User is awaiting approval.'
            ], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    #[OA\Post(
        path: "/logout",
        summary: "User logout",
        description: "Revoke the current API token",
        security: [["bearerAuth" => []]],
        tags: ["Authentication"]
    )]
    #[OA\Response(
        response: 200,
        description: "Logged out successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "message", type: "string", example: "Logged out successfully!")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Unauthorized")]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully!'
        ]);
    }

    #[OA\Post(
        path: "/register",
        summary: "User registration",
        description: "Register a new user. User will be in sandbox mode until approved by admin.",
        tags: ["Authentication"]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                required: ["name", "email", "password"],
                properties: [
                    new OA\Property(property: "name", type: "string", maxLength: 255, example: "User"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", minLength: 7, example: "user123"),
                    new OA\Property(property: "description", type: "string", maxLength: 255, nullable: true, example: "Just a user."),
                    new OA\Property(property: "profile_picture", type: "string", format: "binary", nullable: true, description: "Profile picture. Max size 2MB."),
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: "User registered successfully, awaiting approval",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "message", type: "string", example: "Registered, but approval is expected.")
            ]
        )
    )]
    #[OA\Response(response: 422, description: "Validation error")]
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:7',
            'profile_picture' => ['nullable', 'image', 'max:2048'],
            'description' => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('profile_picture')) {
            $data['profile_picture'] = $request
                ->file('profile_picture')
                ->store('profile-pictures', 'public');
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'description' => $data['description'] ?? null,
            'profile_picture' => $data['profile_picture'] ?? null,
            'is_approved' => false,
        ]);

        return response()->json([
            'message' => 'Registered, but approval is expected.'
        ], 201);
    }

}
