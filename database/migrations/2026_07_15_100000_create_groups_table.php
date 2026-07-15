<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('visibility', 32)->default('public');
            $table->string('avatar_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->unsignedInteger('members_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement(
            'CREATE UNIQUE INDEX groups_tenant_slug_unique ON groups (tenant_id, slug) WHERE deleted_at IS NULL',
        );

        DB::statement(
            'CREATE INDEX groups_tenant_visibility_idx ON groups (tenant_id, visibility) WHERE deleted_at IS NULL',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
