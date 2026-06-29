<?php

use Illuminate\Support\Facades\Route;
use Loom\Features\Theme\Controllers\ThemeController;

Route::prefix(config('loom.admin.route_prefix'))
    ->group(function () {
        Route::get('settings/theme/create', [ThemeController::class, 'create'])
            ->name('admin.settings.theme.create');

        Route::post('settings/theme', [ThemeController::class, 'store'])
            ->name('admin.settings.theme.store');

        Route::post('settings/theme/{slug}/activate', [ThemeController::class, 'activate'])
            ->name('admin.settings.theme.activate');

        Route::post('settings/theme/{slug}/image', [ThemeController::class, 'updateImage'])
            ->name('admin.settings.theme.image');
    });
