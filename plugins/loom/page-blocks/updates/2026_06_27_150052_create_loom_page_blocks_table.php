<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loom_page_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('block_name')->nullable();
            $table->json('block_html');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loom_page_blocks');
    }
};
