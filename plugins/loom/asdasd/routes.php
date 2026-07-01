<?php

use Illuminate\Support\Facades\Route;
use Loom\Asdasd\Controllers\AsdasdsController;

Route::prefix(config('loom.admin.route_prefix'))
    ->name(config('loom.admin.route_name_prefix'))
    ->group(function () {
        Route::resource('asdasd', AsdasdsController::class)->except(['show']);
    });
