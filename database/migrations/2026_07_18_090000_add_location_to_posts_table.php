<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('location_name', 255)->nullable()->after('media_disk');
            $table->decimal('location_lat', 10, 7)->nullable()->after('location_name');
            $table->decimal('location_lng', 10, 7)->nullable()->after('location_lat');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn(['location_name', 'location_lat', 'location_lng']);
        });
    }
};
