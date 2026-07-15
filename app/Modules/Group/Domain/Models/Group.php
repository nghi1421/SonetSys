<?php

declare(strict_types=1);

namespace App\Modules\Group\Domain\Models;

use App\Core\Auth\Domain\Models\User;
use App\Core\Tenancy\Domain\Models\Tenant;
use App\Modules\Feed\Domain\Models\Post;
use App\Modules\Group\Domain\Enums\GroupVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Group extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'owner_id',
        'name',
        'slug',
        'description',
        'visibility',
        'avatar_path',
        'cover_path',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => GroupVisibility::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
