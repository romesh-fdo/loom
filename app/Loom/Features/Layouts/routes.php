<?php

use Illuminate\Support\Facades\Route;
use Loom\Features\Layouts\Controllers\LayoutsController;

Route::prefix(config('loom.admin.route_prefix'))
    ->name(config('loom.admin.route_name_prefix'))
    ->group(function () {
        Route::resource('layouts', LayoutsController::class)
            ->except(['show'])
            ->parameters(['layouts' => 'layoutSlug']);
    });
