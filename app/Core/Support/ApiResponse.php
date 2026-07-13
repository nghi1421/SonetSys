<?php

declare(strict_types=1);

namespace App\Core\Support;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(mixed $data = null, ?array $meta = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null,
            'meta' => $meta,
        ], $status);
    }

    public static function error(string $message, int $status = 422, ?array $meta = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => $message,
            'meta' => $meta,
        ], $status);
    }
}
