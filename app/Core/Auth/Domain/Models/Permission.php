<?php

declare(strict_types=1);

namespace App\Core\Auth\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Permission extends Model
{
    protected $fillable = [
        'slug',
        'group',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}
