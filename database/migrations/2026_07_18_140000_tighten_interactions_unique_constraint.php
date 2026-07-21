<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interactions', function (Blueprint $table): void {
            $table->dropUnique('interactions_unique_per_user');
            $table->unique(
                ['user_id', 'interactable_type', 'interactable_id'],
                'interactions_unique_per_user',
            );
        });
    }

    public function down(): void
    {
        Schema::table('interactions', function (Blueprint $table): void {
            $table->dropUnique('interactions_unique_per_user');
            $table->unique(
                ['user_id', 'interactable_type', 'interactable_id', 'type'],
                'interactions_unique_per_user',
            );
        });
    }
};
