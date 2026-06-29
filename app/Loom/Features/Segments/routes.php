<?php

use Illuminate\Support\Facades\Route;
use Loom\Features\Segments\Controllers\SegmentsController;

Route::prefix(config('loom.admin.route_prefix'))
    ->name(config('loom.admin.route_name_prefix'))
    ->group(function () {
        Route::resource('segments', SegmentsController::class)
            ->except(['show'])
            ->parameters(['segments' => 'segmentSlug']);
    });
