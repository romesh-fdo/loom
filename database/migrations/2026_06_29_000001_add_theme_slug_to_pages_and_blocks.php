<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Loom\Builder\TableNames;

return new class extends Migration
{
    public function up(): void
    {
        $pagesTable = TableNames::applyPrefix('pages');
        $blocksTable = TableNames::applyPrefix('blocks');

        Schema::table($pagesTable, function (Blueprint $table) {
            $table->string('theme_slug')->default('default')->after('id');
        });

        Schema::table($blocksTable, function (Blueprint $table) {
            $table->string('theme_slug')->default('default')->after('id');
        });

        Schema::table($pagesTable, function (Blueprint $table) {
            $table->dropUnique(['url']);
            $table->unique(['theme_slug', 'url']);
            $table->index('theme_slug');
        });

        Schema::table($blocksTable, function (Blueprint $table) {
            $table->index('theme_slug');
        });
    }

    public function down(): void
    {
        $pagesTable = TableNames::applyPrefix('pages');
        $blocksTable = TableNames::applyPrefix('blocks');

        Schema::table($pagesTable, function (Blueprint $table) {
            $table->dropUnique(['theme_slug', 'url']);
            $table->unique('url');
            $table->dropIndex(['theme_slug']);
            $table->dropColumn('theme_slug');
        });

        Schema::table($blocksTable, function (Blueprint $table) {
            $table->dropIndex(['theme_slug']);
            $table->dropColumn('theme_slug');
        });
    }
};
