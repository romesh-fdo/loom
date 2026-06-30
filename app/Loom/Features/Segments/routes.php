<?php

use Illuminate\Support\Facades\Route;
use Loom\Features\Segments\Controllers\SegmentsController;

Route::prefix(config('loom.admin.route_prefix'))
    ->name(config('loom.admin.route_name_prefix'))
    ->group(function () {
        Route::get('segments/tree', [SegmentsController::class, 'tree'])->name('segments.tree');
        Route::post('segments/folders', [SegmentsController::class, 'storeFolder'])->name('segments.folders.store');
        Route::put('segments/folders/{folderPath}', [SegmentsController::class, 'updateFolder'])
            ->where('folderPath', '.*')
            ->name('segments.folders.update');
        Route::delete('segments/folders/{folderPath}', [SegmentsController::class, 'destroyFolder'])
            ->where('folderPath', '.*')
            ->name('segments.folders.destroy');

        Route::get('segments/form/create', [SegmentsController::class, 'formCreate'])->name('segments.form.create');
        Route::get('segments/form/{segmentSlug}', [SegmentsController::class, 'formEdit'])
            ->where('segmentSlug', '.*')
            ->name('segments.form.edit');

        Route::delete('segments/panel/{segmentSlug}', [SegmentsController::class, 'panelDestroy'])
            ->where('segmentSlug', '.*')
            ->name('segments.panel.destroy');

        Route::put('segments/panel/{segmentSlug}/move', [SegmentsController::class, 'panelMove'])
            ->where('segmentSlug', '.*')
            ->name('segments.panel.move');

        Route::get('segments/create', [SegmentsController::class, 'redirectCreate'])->name('segments.create');
        Route::get('segments/{segmentSlug}/edit', [SegmentsController::class, 'redirectEdit'])
            ->where('segmentSlug', '.*')
            ->name('segments.edit');

        Route::resource('segments', SegmentsController::class)
            ->except(['show', 'create', 'edit'])
            ->parameters(['segments' => 'segmentSlug'])
            ->where(['segmentSlug' => '.*']);
    });
