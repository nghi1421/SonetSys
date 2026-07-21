<?php

declare(strict_types=1);

namespace App\Modules\Chat\Http\Controllers;

use App\Core\Auth\Domain\Models\User;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Chat\Application\Services\ChatService;
use App\Modules\Chat\Http\Resources\ConversationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ConversationController extends Controller
{
    public function __construct(
        private readonly ChatService $chat,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $conversations = $this->chat->listConversations((int) $request->user()->id);

        return ApiResponse::success(ConversationResource::collection($conversations));
    }

    public function store(Request $request, User $user): JsonResponse
    {
        $conversation = $this->chat->startOrGetConversation((int) $request->user()->id, $user->id);
        $conversation->other_participant = $user;
        $conversation->is_unread = false;

        return ApiResponse::success(ConversationResource::make($conversation), status: 201);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'unread_count' => $this->chat->unreadCount((int) $request->user()->id),
        ]);
    }
}
