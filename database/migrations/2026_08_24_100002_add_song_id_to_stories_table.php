<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table): void {
            $table->foreignId('song_id')->nullable()->after('caption')->constrained('songs')->nullOnDelete();
            $table->unsignedSmallInteger('song_start_sec')->default(0)->after('song_id');
        });
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('song_id');
            $table->dropColumn('song_start_sec');
        });
    }
};
