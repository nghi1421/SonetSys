<?php

use App\Core\Storage\Domain\Enums\StorageDriver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A true singleton — one row for the whole site's storage backend,
     * replacing the tenants.storage_config jsonb column now that there's
     * only one shared site instead of many tenants.
     */
    public function up(): void
    {
        Schema::create('storage_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('driver')->default(StorageDriver::Local->value);
            $table->string('bucket')->nullable();
            $table->string('region')->nullable();
            $table->string('key')->nullable();
            $table->text('secret')->nullable();
            $table->string('endpoint')->nullable();
            $table->boolean('use_path_style_endpoint')->default(false);
            $table->timestamps();
        });

        DB::table('storage_settings')->insert([
            'driver' => StorageDriver::Local->value,
            'use_path_style_endpoint' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_settings');
    }
};
