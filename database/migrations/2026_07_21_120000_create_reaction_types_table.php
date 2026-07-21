<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reaction_types', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 32)->unique();
            $table->string('label', 64);
            $table->string('emoji', 16)->nullable();
            $table->string('icon_url')->nullable();
            $table->string('icon_disk', 16)->nullable();
            $table->string('icon_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reaction_types');
    }
};
