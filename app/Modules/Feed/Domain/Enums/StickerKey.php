<?php

declare(strict_types=1);

namespace App\Modules\Feed\Domain\Enums;

enum StickerKey: string
{
    case ThumbsUp = 'thumbs_up';
    case HeartEyes = 'heart_eyes';
    case Laugh = 'laugh';
    case Wow = 'wow';
    case Sad = 'sad';
    case Clap = 'clap';
    case Fire = 'fire';
    case Party = 'party';

    public function emoji(): string
    {
        return match ($this) {
            self::ThumbsUp => '👍',
            self::HeartEyes => '😍',
            self::Laugh => '😂',
            self::Wow => '😮',
            self::Sad => '😢',
            self::Clap => '👏',
            self::Fire => '🔥',
            self::Party => '🎉',
        };
    }
}
