<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar_disk', 16)->nullable()->after('avatar_url');
            $table->string('avatar_path')->nullable()->after('avatar_disk');
            $table->string('cover_url')->nullable()->after('avatar_path');
            $table->string('cover_disk', 16)->nullable()->after('cover_url');
            $table->string('cover_path')->nullable()->after('cover_disk');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['avatar_disk', 'avatar_path', 'cover_url', 'cover_disk', 'cover_path']);
        });
    }
};
