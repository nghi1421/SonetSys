<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Users' emails are only unique per tenant (see
     * add_tenant_and_role_to_users_table), so Laravel's stock
     * password_reset_tokens table — keyed by email alone — is ambiguous
     * across tenants. This dedicated table is keyed by user_id instead
     * (left unused going forward; not dropped since other packages may
     * still expect it to exist).
     */
    public function up(): void
    {
        Schema::create('user_password_reset_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_password_reset_tokens');
    }
};
