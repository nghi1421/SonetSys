<?php

declare(strict_types=1);

namespace App\Modules\Song\Http\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Song\Application\Services\SongService;
use App\Modules\Song\Http\Resources\SongResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SongController extends Controller
{
    public function __construct(
        private readonly SongService $songs,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 30), 50);
        $query = $request->filled('q') ? (string) $request->query('q') : null;

        return ApiResponse::success(SongResource::collection($this->songs->search($query, $limit)));
    }
}
