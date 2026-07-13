<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Models;

use App\Core\Auth\Domain\Models\User;
use App\Core\Tenancy\Domain\Models\Tenant;
use App\Modules\Notification\Domain\Enums\NotificationType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

final class Notification extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'notifiable_type',
        'notifiable_id',
        'actor_id',
        'type',
        'notifiable_data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'notifiable_data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function newUniqueId(): string
    {
        // Time-ordered UUID keeps B-tree index locality on this insert-heavy
        // table (see docs/ARCHITECTURE.md §2.3) — a random UUIDv4 would not.
        return (string) Str::orderedUuid();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
