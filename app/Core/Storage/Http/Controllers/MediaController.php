<?php

declare(strict_types=1);

namespace App\Core\Storage\Http\Controllers;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Storage\Application\Services\MediaService;
use App\Core\Storage\Domain\Enums\MediaType;
use App\Core\Storage\Domain\Models\Media;
use App\Core\Storage\Http\Resources\MediaResource;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;

final class MediaController extends Controller
{
    private const DEFAULT_PER_PAGE = 24;

    public function __construct(
        private readonly MediaService $media,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize(PermissionSlug::StorageManage->value);

        $request->validate([
            'type' => ['sometimes', 'nullable', new Enum(MediaType::class)],
        ]);

        $tenantId = (int) $request->user()->tenant_id;
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min((int) $request->query('per_page', self::DEFAULT_PER_PAGE), 100));

        $paginator = $this->media->listForTenant($tenantId, $request->query('type'), $perPage, $page);

        $paginator->getCollection()->each(function (Media $item): void {
            $item->url = $this->media->url($item);
        });

        return ApiResponse::success(MediaResource::collection($paginator->items()), [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function destroy(Request $request, Media $media): JsonResponse
    {
        Gate::authorize(PermissionSlug::StorageManage->value);

        if ($media->tenant_id !== $request->user()->tenant_id) {
            throw new ModelNotFoundException;
        }

        $this->media->delete($media);

        return ApiResponse::success();
    }
}
