<?php

declare(strict_types=1);

namespace App\Core\Storage\Domain\Models;

use App\Core\Auth\Domain\Models\User;
use App\Core\Storage\Domain\Enums\MediaType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'uploaded_by',
        'mediable_type',
        'mediable_id',
        'disk',
        'type',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'size' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }
}
