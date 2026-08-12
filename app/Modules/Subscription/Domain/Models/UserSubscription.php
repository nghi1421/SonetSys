<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Domain\Models;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Subscription\Domain\Enums\SubscriptionPlan;
use App\Modules\Subscription\Domain\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan',
        'status',
        'price',
        'auto_renew',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'cancelled_by',
        'ended_at',
        'last_payment_failed_at',
    ];

    protected function casts(): array
    {
        return [
            'plan' => SubscriptionPlan::class,
            'status' => SubscriptionStatus::class,
            'price' => 'integer',
            'auto_renew' => 'boolean',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancelled_at' => 'datetime',
            'ended_at' => 'datetime',
            'last_payment_failed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
