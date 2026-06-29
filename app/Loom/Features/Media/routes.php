<?php

use Illuminate\Support\Facades\Route;
use Loom\Features\Media\Controllers\MediaController;

Route::prefix(config('loom.admin.route_prefix'))
    ->name(config('loom.admin.route_name_prefix'))
    ->group(function () {
        Route::get('media', [MediaController::class, 'index'])
            ->name('media.index');
    });
