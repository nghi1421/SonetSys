<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status', 32)->default('active');
            $table->jsonb('enabled_modules')->default('[]');
            $table->jsonb('settings')->default('{}');
            $table->timestamps();
        });

        DB::statement('CREATE INDEX tenants_enabled_modules_gin ON tenants USING GIN (enabled_modules)');
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
