<?php

use Illuminate\Support\Facades\Route;
use Loom\Features\Pages\Controllers\PagesController;

Route::prefix(config('loom.admin.route_prefix'))
    ->name(config('loom.admin.route_name_prefix'))
    ->group(function () {
        Route::resource('pages', PagesController::class)
            ->except(['show'])
            ->parameters(['pages' => 'pageSlug']);
    });
