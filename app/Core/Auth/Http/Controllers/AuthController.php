<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\Core\Auth\Application\Services\AuthService;
use App\Core\Auth\Http\Requests\LoginRequest;
use App\Core\Auth\Http\Requests\RegisterRequest;
use App\Core\Auth\Http\Resources\UserResource;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->auth->register($request->toDto());

        return ApiResponse::success([
            'user' => UserResource::make($result['user']->load('role')),
            'token' => $result['token'],
        ], status: 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login($request->toDto());

        return ApiResponse::success([
            'user' => UserResource::make($result['user']->load('role')),
            'token' => $result['token'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return ApiResponse::success();
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(
            UserResource::make($request->user()->load('role'))
        );
    }
}
