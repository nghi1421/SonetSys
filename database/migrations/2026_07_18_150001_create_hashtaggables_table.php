<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Polymorphic pivot mirroring `interactions`' interactable_type/id
        // pattern — posts and comments can both carry hashtags. Unique on all
        // three columns so HashtagService's sync() call is idempotent.
        Schema::create('hashtaggables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hashtag_id')->constrained()->cascadeOnDelete();
            $table->string('hashtaggable_type');
            $table->unsignedBigInteger('hashtaggable_id');

            $table->unique(
                ['hashtag_id', 'hashtaggable_type', 'hashtaggable_id'],
                'hashtaggables_unique',
            );
            $table->index(['hashtaggable_type', 'hashtaggable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hashtaggables');
    }
};
