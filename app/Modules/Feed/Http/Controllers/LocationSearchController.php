<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Feed\Application\Services\LocationSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LocationSearchController extends Controller
{
    public function __construct(
        private readonly LocationSearchService $locations,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        return ApiResponse::success($this->locations->search($validated['q']));
    }
}
