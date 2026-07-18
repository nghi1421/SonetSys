<?php

declare(strict_types=1);

namespace App\Modules\Feed\Domain\Enums;

/**
 * Facebook-style reaction types for posts and comments. A user holds at most
 * one active reaction per target (see the `interactions` table's unique
 * constraint) — picking a new type replaces the old one, it doesn't stack.
 */
enum InteractionType: string
{
    case Like = 'like';
    case Love = 'love';
    case Haha = 'haha';
    case Wow = 'wow';
    case Sad = 'sad';
    case Angry = 'angry';

    public function emoji(): string
    {
        return match ($this) {
            self::Like => '👍',
            self::Love => '❤️',
            self::Haha => '😆',
            self::Wow => '😮',
            self::Sad => '😢',
            self::Angry => '😠',
        };
    }
}
