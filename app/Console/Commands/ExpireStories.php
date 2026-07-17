<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Story\Application\Services\StoryService;
use Illuminate\Console\Command;

final class ExpireStories extends Command
{
    protected $signature = 'stories:expire';

    protected $description = 'Delete stories whose expiry timestamp has passed, along with their attached media.';

    public function handle(StoryService $stories): int
    {
        $expired = $stories->expired();

        foreach ($expired as $story) {
            $stories->delete($story);
        }

        $this->info(sprintf('Expired %d stor%s.', $expired->count(), $expired->count() === 1 ? 'y' : 'ies'));

        return self::SUCCESS;
    }
}
