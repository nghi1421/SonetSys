<?php

use App\Core\Settings\Domain\Enums\CacheDriver;
use App\Core\Settings\Domain\Enums\RedisClient;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A true singleton — one row for the whole site's operational settings
     * (mail server, cache driver, Redis connection, max upload size), seeded
     * from the current .env values so migrating doesn't change live
     * behavior. The primary Postgres connection (DB_*) is deliberately not
     * part of this table — the app needs it to reach this table at all.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('cache_driver')->default(CacheDriver::Database->value);
            $table->string('redis_client')->default(RedisClient::Predis->value);
            $table->string('redis_host')->default('127.0.0.1');
            $table->unsignedInteger('redis_port')->default(6379);
            $table->text('redis_password')->nullable();
            $table->unsignedInteger('redis_database')->default(0);
            $table->string('mail_host')->default('127.0.0.1');
            $table->unsignedInteger('mail_port')->default(2525);
            $table->string('mail_username')->nullable();
            $table->text('mail_password')->nullable();
            $table->string('mail_encryption')->nullable();
            $table->string('mail_from_address')->default('hello@example.com');
            $table->string('mail_from_name')->default('Sonetsys');
            $table->unsignedInteger('max_upload_size_kb')->default(20480);
            $table->timestamps();
        });

        $cacheDriver = CacheDriver::tryFrom((string) env('CACHE_STORE')) ?? CacheDriver::Database;
        $redisClient = RedisClient::tryFrom((string) env('REDIS_CLIENT')) ?? RedisClient::Predis;
        $redisPassword = env('REDIS_PASSWORD');
        $mailPassword = env('MAIL_PASSWORD');

        DB::table('system_settings')->insert([
            'cache_driver' => $cacheDriver->value,
            'redis_client' => $redisClient->value,
            'redis_host' => env('REDIS_HOST', '127.0.0.1'),
            'redis_port' => (int) env('REDIS_PORT', 6379),
            'redis_password' => $redisPassword !== null ? Crypt::encryptString((string) $redisPassword) : null,
            'redis_database' => (int) env('REDIS_DB', 0),
            'mail_host' => env('MAIL_HOST', '127.0.0.1'),
            'mail_port' => (int) env('MAIL_PORT', 2525),
            'mail_username' => env('MAIL_USERNAME'),
            'mail_password' => $mailPassword !== null ? Crypt::encryptString((string) $mailPassword) : null,
            'mail_encryption' => env('MAIL_SCHEME'),
            'mail_from_address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
            'mail_from_name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Sonetsys')),
            'max_upload_size_kb' => 20480,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
