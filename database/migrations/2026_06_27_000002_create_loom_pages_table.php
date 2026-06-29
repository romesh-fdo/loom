<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Loom\Builder\TableNames;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(TableNames::applyPrefix('pages'), function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url')->unique();
            $table->json('sections')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(TableNames::applyPrefix('pages'));
    }
};
