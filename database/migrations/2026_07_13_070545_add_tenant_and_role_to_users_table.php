<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['email']);

            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('role_id')->after('tenant_id')->constrained()->restrictOnDelete();
            $table->string('status', 32)->default('active')->after('password');
            $table->string('avatar_url')->nullable()->after('status');
            $table->timestamp('last_login_at')->nullable()->after('avatar_url');

            $table->unique(['tenant_id', 'email']);
        });

        DB::statement('CREATE UNIQUE INDEX users_email_unique_superadmin ON users (email) WHERE tenant_id IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_email_unique_superadmin');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['tenant_id', 'email']);
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['status', 'avatar_url', 'last_login_at']);

            $table->unique(['email']);
        });
    }
};
