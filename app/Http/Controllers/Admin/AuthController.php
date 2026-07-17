<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Authenticate a panel user and return a JWT.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $token = auth('api')->attempt($credentials);

        if (! $token) {
            return response()->json([
                'data' => null,
                'meta' => ['message' => 'Invalid credentials'],
            ], 401);
        }

        return response()->json([
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ],
            'meta' => [],
        ]);
    }

    /**
     * Return the currently authenticated user.
     */
    public function me(): JsonResponse
    {
        return response()->json([
            'data' => auth('api')->user(),
            'meta' => [],
        ]);
    }

    /**
     * Invalidate the current token.
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json([
            'data' => null,
            'meta' => ['message' => 'Successfully logged out'],
        ]);
    }
}
