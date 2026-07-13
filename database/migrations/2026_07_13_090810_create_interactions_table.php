<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('interactable_type');
            $table->unsignedBigInteger('interactable_id');
            $table->string('type', 32)->default('like');
            $table->timestamp('created_at')->nullable();

            $table->unique(
                ['user_id', 'interactable_type', 'interactable_id', 'type'],
                'interactions_unique_per_user',
            );
            $table->index(['interactable_type', 'interactable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};
