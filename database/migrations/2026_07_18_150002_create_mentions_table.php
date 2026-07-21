<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Separate from `hashtaggables` since this links to `users`, not
        // `hashtags` — still the same interactable_type/id polymorphic shape.
        Schema::create('mentions', function (Blueprint $table): void {
            $table->id();
            $table->string('mentionable_type');
            $table->unsignedBigInteger('mentionable_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->unique(
                ['mentionable_type', 'mentionable_id', 'user_id'],
                'mentions_unique',
            );
            $table->index(['mentionable_type', 'mentionable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentions');
    }
};
