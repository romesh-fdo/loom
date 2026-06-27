<?php

use Illuminate\Support\Facades\Route;
use Loom\Pages\Controllers\PagesController;

Route::prefix(config('loom.admin.route_prefix'))
    ->name(config('loom.admin.route_name_prefix'))
    ->group(function () {
        Route::get('pages', [PagesController::class, 'index'])->name('pages.index');
    });
