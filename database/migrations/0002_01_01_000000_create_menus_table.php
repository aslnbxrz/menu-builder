<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(config('menu-builder.menu.table'), function (Blueprint $table) {
            $table->id();
            $table->jsonb('title')->nullable();
            $table->jsonb('description')->nullable();
            $table->string('alias')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('menu-builder.menu.table'));
    }
};
