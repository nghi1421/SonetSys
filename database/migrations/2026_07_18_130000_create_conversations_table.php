<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_one_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_two_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('user_one_last_read_at')->nullable();
            $table->timestamp('user_two_last_read_at')->nullable();
            $table->timestamps();

            // Normalized (user_one_id < user_two_id) by the repository before
            // any create/lookup, so a pair is never duplicated in either order.
            $table->unique(['user_one_id', 'user_two_id']);
            $table->index('user_one_id');
            $table->index('user_two_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
