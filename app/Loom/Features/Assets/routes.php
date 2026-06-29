<?php

use Illuminate\Support\Facades\Route;
use Loom\Features\Assets\Controllers\AssetsController;

Route::prefix(config('loom.admin.route_prefix'))
    ->name(config('loom.admin.route_name_prefix'))
    ->group(function () {
        Route::get('assets', [AssetsController::class, 'index'])
            ->name('assets.index');
    });
