<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('media_type', 16);
            $table->string('media_disk', 16);
            $table->string('media_path');
            $table->string('caption', 200)->nullable();
            $table->timestamp('published_at');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['author_id', 'expires_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
