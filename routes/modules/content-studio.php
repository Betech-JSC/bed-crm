<?php

use App\Http\Controllers\ContentStudioController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('content-studio', [ContentStudioController::class, 'index'])->name('content-studio.index');
    Route::post('content-studio/generate', [ContentStudioController::class, 'generate'])->name('content-studio.generate');
    Route::post('content-studio/save', [ContentStudioController::class, 'saveContent'])->name('content-studio.save');
    Route::post('content-studio/publish', [ContentStudioController::class, 'publish'])->name('content-studio.publish');
    Route::post('content-studio/thumbnail', [ContentStudioController::class, 'regenerateThumbnail'])->name('content-studio.thumbnail');
    Route::post('content-studio/generate-seo', [ContentStudioController::class, 'generateSeo'])->name('content-studio.generate-seo');
    Route::post('content-studio/save-seo', [ContentStudioController::class, 'saveSeo'])->name('content-studio.save-seo');
    Route::post('content-studio/article-image', [ContentStudioController::class, 'generateArticleImage'])->name('content-studio.article-image');
});
