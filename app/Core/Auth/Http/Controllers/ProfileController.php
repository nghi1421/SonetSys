<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\Core\Auth\Domain\Models\User;
use App\Core\Auth\Http\Requests\UpdateProfileRequest;
use App\Core\Auth\Http\Resources\UserResource;
use App\Core\Storage\Application\Services\MediaService;
use App\Core\Storage\Application\Services\StorageService;
use App\Core\Storage\Domain\Enums\MediaType;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

final class ProfileController extends Controller
{
    public function __construct(
        private readonly StorageService $storage,
        private readonly MediaService $media,
    ) {}

    /**
     * Self-service avatar/cover upload — always operates on the authenticated
     * user's own record, so (unlike UserController::update()) no Gate check
     * is needed. Each of avatar/cover/remove_avatar/remove_cover is handled
     * independently: a single request may replace one file, remove the
     * other, both, or neither.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->applySlot($request, $user, 'avatar', 'avatars');
        $this->applySlot($request, $user, 'cover', 'covers');

        return ApiResponse::success(UserResource::make($user->fresh()));
    }

    private function applySlot(UpdateProfileRequest $request, User $user, string $slot, string $directory): void
    {
        if ($request->hasFile($slot)) {
            $this->replaceFile($user, $request->file($slot), $directory, $slot);

            return;
        }

        if ($request->boolean("remove_{$slot}")) {
            $this->removeFile($user, $slot);
        }
    }

    /**
     * Stores the new file and saves it on the user record BEFORE deleting
     * the old one — a failure mid-upload never leaves the user with neither
     * file, only (at worst) an orphaned old file still on disk.
     */
    private function replaceFile(User $user, UploadedFile $file, string $directory, string $slot): void
    {
        $oldDisk = $user->{"{$slot}_disk"};
        $oldPath = $user->{"{$slot}_path"};

        $stored = $this->storage->store($file, $directory);

        $user->{"{$slot}_disk"} = $stored['disk'];
        $user->{"{$slot}_path"} = $stored['path'];
        $user->{"{$slot}_url"} = $this->storage->url($stored['disk'], $stored['path']);
        $user->save();

        $this->media->attach($stored, (int) $user->id, MediaType::Image, $file, $user);

        if ($oldDisk !== null && $oldPath !== null) {
            $this->storage->delete($oldDisk, $oldPath);
        }
    }

    private function removeFile(User $user, string $slot): void
    {
        $disk = $user->{"{$slot}_disk"};
        $path = $user->{"{$slot}_path"};

        if ($disk === null || $path === null) {
            return;
        }

        $this->storage->delete($disk, $path);

        $user->{"{$slot}_disk"} = null;
        $user->{"{$slot}_path"} = null;
        $user->{"{$slot}_url"} = null;
        $user->save();
    }
}
