<?php

use Illuminate\Support\Facades\Route;
use Loom\PageBlocks\Controllers\PageBlocksController;

Route::prefix(config('loom.admin.route_prefix'))
    ->name(config('loom.admin.route_name_prefix'))
    ->group(function () {
        Route::resource('page-blocks', PageBlocksController::class)->except(['show']);
    });
