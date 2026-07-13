<?php

declare(strict_types=1);

namespace App\Modules\Feed\Domain\Enums;

enum PostVisibility: string
{
    case Public = 'public';
    case TenantOnly = 'tenant_only';
    case Private = 'private';
}
