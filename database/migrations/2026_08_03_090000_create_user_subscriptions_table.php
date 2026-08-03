<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan');
            $table->string('status');
            $table->unsignedInteger('price');
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('current_period_start');
            $table->timestamp('current_period_end');
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('last_payment_failed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'current_period_end']);
            $table->index(['user_id', 'status']);
        });

        DB::statement(
            "CREATE UNIQUE INDEX user_subscriptions_one_active_per_user ON user_subscriptions (user_id) WHERE status = 'active'"
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS user_subscriptions_one_active_per_user');
        Schema::dropIfExists('user_subscriptions');
    }
};
