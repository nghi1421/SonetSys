<?php

declare(strict_types=1);

namespace App\Modules\Search\Http\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Search\Application\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SearchController extends Controller
{
    public function __construct(
        private readonly SearchService $search,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $user = $request->user();

        return ApiResponse::success($this->search->search($validated['q'], $user->id));
    }
}
