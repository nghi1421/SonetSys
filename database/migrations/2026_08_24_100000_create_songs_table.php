<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('songs', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 191);
            $table->string('artist', 191)->nullable();
            $table->string('audio_disk', 16);
            $table->string('audio_path');
            $table->string('cover_disk', 16)->nullable();
            $table->string('cover_path')->nullable();
            $table->unsignedInteger('duration_sec')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('songs');
    }
};
