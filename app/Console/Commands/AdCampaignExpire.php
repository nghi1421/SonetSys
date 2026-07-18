<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Advertising\Application\Services\AdCampaignService;
use Illuminate\Console\Command;

final class AdCampaignExpire extends Command
{
    protected $signature = 'ads:expire';

    protected $description = 'Mark approved ad campaigns whose end date has passed as completed.';

    public function handle(AdCampaignService $campaigns): int
    {
        $expired = $campaigns->expireDueCampaigns();

        $this->info(sprintf('Completed %d ad campaign%s.', $expired->count(), $expired->count() === 1 ? '' : 's'));

        return self::SUCCESS;
    }
}
