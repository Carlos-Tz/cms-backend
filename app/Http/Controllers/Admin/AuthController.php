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
                'status' => 'error',
                'data' => null,
                'meta' => ['message' => 'Credenciales inválidas'],
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ],
            'meta' => ['message' => 'Sesión iniciada correctamente'],
        ]);
    }

    /**
     * Return the currently authenticated user.
     */
    public function me(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
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
            'status' => 'success',
            'data' => null,
            'meta' => ['message' => 'Sesión cerrada correctamente'],
        ]);
    }
}
