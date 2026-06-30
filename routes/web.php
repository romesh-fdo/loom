<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ThemeAssetCombineController;
use App\Http\Controllers\ThemeAssetController;
use Illuminate\Support\Facades\Route;

Route::get('/theme/{theme}/combine/{signature}.{extension}', [ThemeAssetCombineController::class, 'show'])
    ->where('extension', 'css|js')
    ->name('theme.assets.combine');

Route::get('/theme/{theme}/assets/{path}', [ThemeAssetController::class, 'show'])
    ->where('path', '.*')
    ->name('theme.assets');

Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
Route::get('/admin/settings', [AdminController::class, 'settings'])->name('admin.settings');

Route::get('/', [PageController::class, 'show'])->name('loom.page.home');

Route::get('/{path}', [PageController::class, 'show'])
    ->where('path', '^(?!admin(?:/|$)|theme(?:/|$)|media(?:/|$)|uploads(?:/|$)|vendor(?:/|$)|build(?:/|$)).*$')
    ->name('loom.page.show');
