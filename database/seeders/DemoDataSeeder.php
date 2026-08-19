<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Support\DemoAdvertisingStep;
use Database\Seeders\Support\DemoChatStep;
use Database\Seeders\Support\DemoFeedStep;
use Database\Seeders\Support\DemoFinanceStep;
use Database\Seeders\Support\DemoGroupStep;
use Database\Seeders\Support\DemoReportStep;
use Database\Seeders\Support\DemoSocialGraphStep;
use Database\Seeders\Support\DemoUserStep;
use Illuminate\Database\Seeder;

/**
 * Populates the local/testing database with a realistic, richly-connected
 * dataset (users, groups, posts, comments, reactions, follows, wallets,
 * subscriptions, ad campaigns, chat, reports) built entirely through the
 * real Application Services — so hashtags, mentions, counters, and
 * notifications come from actual business logic, not hand-faked rows.
 *
 * Never runs outside local/testing — this is demo fixture data, not
 * something that belongs in a production database.
 */
final class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $this->command?->info('Seeding demo users...');
        $users = (new DemoUserStep)->run();

        $this->command?->info('Seeding wallets and subscriptions...');
        app(DemoFinanceStep::class)->run($users);

        $this->command?->info('Seeding the follow graph...');
        $mutualPairs = app(DemoSocialGraphStep::class)->run($users);

        $this->command?->info('Seeding groups and memberships...');
        $groups = app(DemoGroupStep::class)->run($users);

        $this->command?->info('Seeding posts, comments, and reactions...');
        app(DemoFeedStep::class)->run($users, $groups);

        $this->command?->info('Seeding ad campaigns...');
        app(DemoAdvertisingStep::class)->run();

        $this->command?->info('Seeding conversations and messages...');
        app(DemoChatStep::class)->run($mutualPairs);

        $this->command?->info('Seeding reports...');
        app(DemoReportStep::class)->run();
    }
}
