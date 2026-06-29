<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Loom\Builder\TableNames;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(TableNames::applyPrefix('blocks'), function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('code')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(TableNames::applyPrefix('blocks'));
    }
};
