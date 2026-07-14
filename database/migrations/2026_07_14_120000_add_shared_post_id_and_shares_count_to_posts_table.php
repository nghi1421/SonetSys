<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->foreignId('shared_post_id')->nullable()->after('author_id')
                ->constrained('posts')->nullOnDelete();
            $table->unsignedInteger('shares_count')->default(0)->after('comments_count');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('shared_post_id');
            $table->dropColumn('shares_count');
        });
    }
};
