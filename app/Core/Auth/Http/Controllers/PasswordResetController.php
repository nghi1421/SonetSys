<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\Core\Auth\Application\Services\AuthService;
use App\Core\Auth\Http\Requests\ForgotPasswordRequest;
use App\Core\Auth\Http\Requests\ResetPasswordRequest;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class PasswordResetController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
    ) {}

    public function sendResetLink(ForgotPasswordRequest $request): JsonResponse
    {
        $this->auth->sendPasswordResetLink($request->toDto());

        return ApiResponse::success(null, [
            'message' => 'If that email is registered, a password reset link has been sent.',
        ]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $this->auth->resetPassword($request->toDto());

        return ApiResponse::success(null, [
            'message' => 'Your password has been reset.',
        ]);
    }
}
