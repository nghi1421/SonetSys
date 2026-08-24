<?php

declare(strict_types=1);

namespace App\Modules\Song\Http\Controllers\Admin;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Song\Application\Services\SongService;
use App\Modules\Song\Domain\Models\Song;
use App\Modules\Song\Http\Requests\CreateSongRequest;
use App\Modules\Song\Http\Requests\UpdateSongRequest;
use App\Modules\Song\Http\Resources\SongResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class SongController extends Controller
{
    public function __construct(
        private readonly SongService $songs,
    ) {}

    public function index(): JsonResponse
    {
        Gate::authorize(PermissionSlug::SongsManage->value);

        return ApiResponse::success(SongResource::collection($this->songs->all()));
    }

    public function store(CreateSongRequest $request): JsonResponse
    {
        Gate::authorize(PermissionSlug::SongsManage->value);

        $song = $this->songs->create($request->toDto(), (int) $request->user()->id);

        return ApiResponse::success(SongResource::make($song), status: 201);
    }

    public function update(UpdateSongRequest $request, Song $song): JsonResponse
    {
        Gate::authorize(PermissionSlug::SongsManage->value);

        $song = $this->songs->update($song, $request->toDto(), (int) $request->user()->id);

        return ApiResponse::success(SongResource::make($song));
    }

    public function destroy(Request $request, Song $song): JsonResponse
    {
        Gate::authorize(PermissionSlug::SongsManage->value);

        $this->songs->delete($song);

        return ApiResponse::success();
    }
}
