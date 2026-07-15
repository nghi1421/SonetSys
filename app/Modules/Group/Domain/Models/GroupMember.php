<?php

declare(strict_types=1);

namespace App\Modules\Group\Domain\Models;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Group\Domain\Enums\GroupMemberRole;
use App\Modules\Group\Domain\Enums\GroupMemberStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class GroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'user_id',
        'role',
        'status',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => GroupMemberRole::class,
            'status' => GroupMemberStatus::class,
            'joined_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
