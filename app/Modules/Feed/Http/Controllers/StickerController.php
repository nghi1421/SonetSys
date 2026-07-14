<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Feed\Domain\Enums\StickerKey;
use Illuminate\Http\JsonResponse;

final class StickerController extends Controller
{
    public function index(): JsonResponse
    {
        $stickers = collect(StickerKey::cases())->map(fn (StickerKey $sticker) => [
            'key' => $sticker->value,
            'emoji' => $sticker->emoji(),
        ]);

        return ApiResponse::success($stickers);
    }
}
