<?php

use Illuminate\Support\Facades\Route;
use Loom\Features\PluginBuilder\Controllers\PluginBuilderController;

Route::prefix(config('loom.admin.route_prefix'))
    ->name(config('loom.admin.route_name_prefix'))
    ->group(function () {
        Route::get('plugin-builder/icons', [PluginBuilderController::class, 'icons'])
            ->name('plugin-builder.icons');
        Route::get('plugin-builder', [PluginBuilderController::class, 'index'])
            ->name('plugin-builder.index');
        Route::get('plugin-builder/create', [PluginBuilderController::class, 'create'])
            ->name('plugin-builder.create');
        Route::post('plugin-builder', [PluginBuilderController::class, 'store'])
            ->name('plugin-builder.store');
        Route::get('plugin-builder/{pluginSlug}/edit', [PluginBuilderController::class, 'edit'])
            ->where('pluginSlug', '[a-z][a-z0-9-]*')
            ->name('plugin-builder.edit');
        Route::put('plugin-builder/{pluginSlug}', [PluginBuilderController::class, 'update'])
            ->where('pluginSlug', '[a-z][a-z0-9-]*')
            ->name('plugin-builder.update');
    });
