<?php

declare(strict_types=1);

namespace App\Modules\Search;

use Illuminate\Support\ServiceProvider;

/**
 * Deliberately has no bindings — SearchService queries Feed/Auth/Group's
 * existing Eloquent models directly for a handful of capped, read-only
 * preview queries (see plan: "no need for a unified query-builder
 * abstraction across four different models"). The module exists to keep
 * this cross-cutting concern out of Feed rather than to introduce a new
 * repository layer of its own.
 */
final class SearchServiceProvider extends ServiceProvider {}
