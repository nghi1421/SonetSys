<?php

declare(strict_types=1);

namespace App\Modules\Chat\Http\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Chat\Application\Services\ChatService;
use App\Modules\Chat\Domain\Models\Conversation;
use App\Modules\Chat\Http\Requests\SendMessageRequest;
use App\Modules\Chat\Http\Resources\MessageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MessageController extends Controller
{
    public function __construct(
        private readonly ChatService $chat,
    ) {}

    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $viewerId = (int) $request->user()->id;
        $limit = min((int) $request->query('limit', 30), 50);

        $result = $this->chat->messagesFor($conversation->id, $viewerId, $request->query('cursor'), $limit);

        // Marking read is an explicit side effect of successfully fetching
        // the thread, kept out of messagesFor() so that method stays a pure read.
        $this->chat->markThreadRead($conversation, $viewerId);

        return ApiResponse::success(MessageResource::collection($result['items']), [
            'next_cursor' => $result['next_cursor'],
        ]);
    }

    public function store(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $message = $this->chat->sendMessage($conversation->id, (int) $request->user()->id, $request->body());

        return ApiResponse::success(MessageResource::make($message), status: 201);
    }
}
