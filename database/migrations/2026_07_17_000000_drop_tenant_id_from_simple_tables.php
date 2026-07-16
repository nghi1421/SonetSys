<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS posts_tenant_published_idx');
        DB::statement('DROP INDEX IF EXISTS posts_tenant_author_idx');

        Schema::table('posts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tenant_id');
        });

        DB::statement(
            'CREATE INDEX posts_published_idx ON posts (published_at DESC) WHERE deleted_at IS NULL',
        );
        DB::statement('CREATE INDEX posts_author_idx ON posts (author_id) WHERE deleted_at IS NULL');

        Schema::table('comments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::table('interactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'created_at']);
            $table->dropConstrainedForeignId('tenant_id');
            $table->index('created_at');
        });

        Schema::table('static_pages', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id']);
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::table('media', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'mediable_type', 'mediable_id']);
            $table->dropIndex(['tenant_id', 'type']);
            $table->dropConstrainedForeignId('tenant_id');
            $table->index(['mediable_type', 'mediable_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->dropIndex(['mediable_type', 'mediable_id']);
            $table->dropIndex(['type']);
            $table->foreignId('tenant_id')->after('id')->constrained()->cascadeOnDelete();
            $table->index(['tenant_id', 'mediable_type', 'mediable_id']);
            $table->index(['tenant_id', 'type']);
        });

        Schema::table('static_pages', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->after('id')->constrained()->cascadeOnDelete();
            $table->index('tenant_id');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
            $table->foreignId('tenant_id')->after('id')->constrained()->cascadeOnDelete();
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::table('interactions', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('comments', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->after('id')->constrained()->cascadeOnDelete();
        });

        DB::statement('DROP INDEX IF EXISTS posts_published_idx');
        DB::statement('DROP INDEX IF EXISTS posts_author_idx');

        Schema::table('posts', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->after('id')->constrained()->cascadeOnDelete();
        });

        DB::statement(
            'CREATE INDEX posts_tenant_published_idx ON posts (tenant_id, published_at DESC) WHERE deleted_at IS NULL',
        );
        DB::statement('CREATE INDEX posts_tenant_author_idx ON posts (tenant_id, author_id) WHERE deleted_at IS NULL');
    }
};
