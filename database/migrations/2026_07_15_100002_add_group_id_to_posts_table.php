<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->foreignId('group_id')->nullable()->after('shared_post_id')->constrained('groups')->cascadeOnDelete();
        });

        DB::statement(
            'CREATE INDEX posts_group_published_idx ON posts (group_id, published_at DESC) WHERE deleted_at IS NULL AND group_id IS NOT NULL',
        );
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('group_id');
        });
    }
};
