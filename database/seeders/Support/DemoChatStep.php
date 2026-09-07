<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Modules\Chat\Application\Services\ChatService;

final class DemoChatStep
{
    private const MAX_CONVERSATIONS = 25;

    private const MIN_MESSAGES = 3;

    private const MAX_MESSAGES = 10;

    public function __construct(
        private readonly ChatService $chat,
    ) {}

    /**
     * @param  list<array{0: int, 1: int}>  $mutualPairs
     */
    public function run(array $mutualPairs): void
    {
        $pairs = collect($mutualPairs)->shuffle()->take(self::MAX_CONVERSATIONS);

        foreach ($pairs as [$userA, $userB]) {
            $conversation = $this->chat->startOrGetConversation($userA, $userB);

            $messageCount = fake()->numberBetween(self::MIN_MESSAGES, self::MAX_MESSAGES);
            $sender = fake()->boolean() ? $userA : $userB;
            $other = $sender === $userA ? $userB : $userA;

            for ($i = 0; $i < $messageCount; $i++) {
                $this->chat->sendMessage(
                    $conversation->id,
                    $sender,
                    fake()->randomElement(DemoContent::chatLines()),
                );

                [$sender, $other] = [$other, $sender];
            }
        }
    }
}
