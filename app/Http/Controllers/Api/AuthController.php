<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\JWTGuard;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create($request->validated());
        $token = $this->guard()->login($user);

        return $this->success(
            data: [
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => $this->guard()->factory()->getTTL() * 60,
            ],
            message: 'User registered successfully.',
            status: Response::HTTP_CREATED,
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $token = $this->guard()->attempt($request->validated());

        if ($token === false) {
            throw new AuthenticationException('Invalid email or password.');
        }

        $user = $this->guard()->userOrFail();

        return $this->success(
            data: [
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => $this->guard()->factory()->getTTL() * 60,
            ],
            message: 'Login successful.',
        );
    }

    public function me(): JsonResponse
    {
        $user = $this->guard()->userOrFail();

        return $this->success(
            data: new UserResource($user),
            message: 'Authenticated user retrieved successfully.',
        );
    }

    public function logout(): JsonResponse
    {
        $this->guard()->logout();

        return $this->success(message: 'Logout successful.');
    }

    private function guard(): JWTGuard
    {
        $guard = Auth::guard('api');

        return $guard;
    }
}
