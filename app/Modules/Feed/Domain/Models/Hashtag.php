<?php

declare(strict_types=1);

namespace App\Modules\Feed\Domain\Models;

use Illuminate\Database\Eloquent\Model;

final class Hashtag extends Model
{
    protected $fillable = [
        'tag',
    ];
}
