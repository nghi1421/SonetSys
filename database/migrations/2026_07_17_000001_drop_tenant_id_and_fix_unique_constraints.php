<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique(['tenant_id', 'slug']);
            $table->dropConstrainedForeignId('tenant_id');
            $table->unique('slug');
        });

        DB::statement('DROP INDEX IF EXISTS users_email_unique_superadmin');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['tenant_id', 'email']);
            $table->dropConstrainedForeignId('tenant_id');
            $table->unique('email');
        });

        Schema::table('menu_items', function (Blueprint $table): void {
            $table->dropUnique(['tenant_id', 'slug']);
            $table->dropIndex(['tenant_id', 'position']);
            $table->dropConstrainedForeignId('tenant_id');
            $table->unique('slug');
            $table->index('position');
        });

        DB::statement('DROP INDEX IF EXISTS groups_tenant_slug_unique');
        DB::statement('DROP INDEX IF EXISTS groups_tenant_visibility_idx');

        Schema::table('groups', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tenant_id');
        });

        DB::statement('CREATE UNIQUE INDEX groups_slug_unique ON groups (slug) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX groups_visibility_idx ON groups (visibility) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS groups_slug_unique');
        DB::statement('DROP INDEX IF EXISTS groups_visibility_idx');

        Schema::table('groups', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->after('id')->constrained()->cascadeOnDelete();
        });

        DB::statement(
            'CREATE UNIQUE INDEX groups_tenant_slug_unique ON groups (tenant_id, slug) WHERE deleted_at IS NULL',
        );
        DB::statement(
            'CREATE INDEX groups_tenant_visibility_idx ON groups (tenant_id, visibility) WHERE deleted_at IS NULL',
        );

        Schema::table('menu_items', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropIndex(['position']);
            $table->foreignId('tenant_id')->after('id')->constrained()->cascadeOnDelete();
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'position']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['email']);
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unique(['tenant_id', 'email']);
        });

        DB::statement('CREATE UNIQUE INDEX users_email_unique_superadmin ON users (email) WHERE tenant_id IS NULL');

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->foreignId('tenant_id')->after('id')->constrained()->cascadeOnDelete();
            $table->unique(['tenant_id', 'slug']);
        });
    }
};
