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
        $menuTableName = config('menu-builder.menu.table');
        $menuItemTableName = config('menu-builder.menu_item.table');
        Schema::create($menuItemTableName, function (Blueprint $table) use ($menuItemTableName, $menuTableName) {
            $table->id();
            $table->foreignId('menu_id')->index()->constrained($menuTableName)->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->index()->constrained($menuItemTableName, 'id')->restrictOnDelete();
            $table->nullableMorphs('menuable');
            $table->string('menuable_value')->nullable();
            $table->jsonb('title')->nullable();
            $table->string('link')->nullable();
            $table->string('type');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->jsonb('meta')->nullable();
            $table->timestamps();
        });

        DB::statement(
            <<<SQL
                CREATE INDEX {$menuItemTableName}_visible_sorted_idx
                ON $menuItemTableName (menu_id, parent_id, sort)
                WHERE is_active = true
                SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('menu-builder.menu_item.table');
        DB::statement(
            <<<SQL
                DROP INDEX IF EXISTS {$tableName}_visible_sorted_idx
                SQL
        );

        Schema::dropIfExists($tableName);
    }
};
