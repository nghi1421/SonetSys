<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Notification\Application\Services\NotificationService;
use App\Modules\Notification\Domain\Models\Notification;
use App\Modules\Notification\Http\Resources\NotificationResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = $this->notifications->listForUser($user->tenant_id, $user->id);

        return ApiResponse::success(NotificationResource::collection($items), [
            'unread_count' => $this->notifications->unreadCount($user->tenant_id, $user->id),
        ]);
    }

    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        $this->ensureOwnedByUser($request, $notification);

        $this->notifications->markAsRead($notification);

        return ApiResponse::success();
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->notifications->markAllAsRead($user->tenant_id, $user->id);

        return ApiResponse::success();
    }

    private function ensureOwnedByUser(Request $request, Notification $notification): void
    {
        $user = $request->user();

        if (
            $notification->tenant_id !== $user->tenant_id
            || $notification->notifiable_type !== 'user'
            || $notification->notifiable_id !== $user->id
        ) {
            throw new ModelNotFoundException;
        }
    }
}
