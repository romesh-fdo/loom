<?php

use Illuminate\Support\Facades\Route;
use Loom\Features\Theme\Controllers\ThemeController;

Route::prefix(config('loom.admin.route_prefix'))
    ->group(function () {
        Route::get('settings/theme/create', [ThemeController::class, 'create'])
            ->name('admin.settings.theme.create');

        Route::post('settings/theme', [ThemeController::class, 'store'])
            ->name('admin.settings.theme.store');

        Route::get('settings/theme/{slug}/edit', [ThemeController::class, 'edit'])
            ->name('admin.settings.theme.edit');

        Route::put('settings/theme/{slug}', [ThemeController::class, 'update'])
            ->name('admin.settings.theme.update');

        Route::post('settings/theme/{slug}/activate', [ThemeController::class, 'activate'])
            ->name('admin.settings.theme.activate');

        Route::post('settings/theme/{slug}/image', [ThemeController::class, 'updateImage'])
            ->name('admin.settings.theme.image');

        Route::delete('settings/theme/{slug}', [ThemeController::class, 'destroy'])
            ->name('admin.settings.theme.destroy');
    });
