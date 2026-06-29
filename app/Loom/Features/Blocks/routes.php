<?php

use Illuminate\Support\Facades\Route;
use Loom\Features\Blocks\Controllers\BlocksController;

Route::prefix(config('loom.admin.route_prefix'))
    ->name(config('loom.admin.route_name_prefix'))
    ->group(function () {
        Route::resource('blocks', BlocksController::class)->except(['show']);
    });
