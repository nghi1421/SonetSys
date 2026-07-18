<?php

declare(strict_types=1);

namespace App\Modules\Advertising\Domain\Models;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Advertising\Domain\Enums\AdCampaignStatus;
use App\Modules\Feed\Domain\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AdCampaign extends Model
{
    protected $fillable = [
        'post_id',
        'advertiser_id',
        'status',
        'budget',
        'starts_at',
        'ends_at',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AdCampaignStatus::class,
            'budget' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advertiser_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
