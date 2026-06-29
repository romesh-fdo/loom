<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Loom\Builder\TableNames;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists(TableNames::applyPrefix('pages'));
        Schema::dropIfExists(TableNames::applyPrefix('blocks'));
    }

    public function down(): void
    {
        //
    }
};
