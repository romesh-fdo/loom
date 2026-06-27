<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loom_page_blocks', function (Blueprint $table) {
            $table->string('resdwewe2')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('loom_page_blocks', function (Blueprint $table) {
            $table->dropColumn('resdwewe2');
        });
    }
};
