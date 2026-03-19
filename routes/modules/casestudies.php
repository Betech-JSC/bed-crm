<?php

use App\Http\Controllers\CaseStudyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // ═══════════════════════════════════════════
    // Case Studies
    // ═══════════════════════════════════════════
    Route::get('/case-studies', [CaseStudyController::class, 'index'])->name('case-studies');
    Route::get('/case-studies/create', [CaseStudyController::class, 'create'])->name('case-studies.create');
    Route::post('/case-studies', [CaseStudyController::class, 'store'])->name('case-studies.store');
    Route::get('/case-studies/{caseStudy}', [CaseStudyController::class, 'show'])->name('case-studies.show');
    Route::get('/case-studies/{caseStudy}/edit', [CaseStudyController::class, 'edit'])->name('case-studies.edit');
    Route::put('/case-studies/{caseStudy}', [CaseStudyController::class, 'update'])->name('case-studies.update');
    Route::delete('/case-studies/{caseStudy}', [CaseStudyController::class, 'destroy'])->name('case-studies.destroy');

    // Status
    Route::post('/case-studies/{caseStudy}/publish', [CaseStudyController::class, 'publish'])->name('case-studies.publish');
    Route::post('/case-studies/{caseStudy}/toggle-featured', [CaseStudyController::class, 'toggleFeatured'])->name('case-studies.toggle-featured');

    // Media
    Route::post('/case-studies/{caseStudy}/media', [CaseStudyController::class, 'storeMedia'])->name('case-studies.media.store');
    Route::delete('/case-study-media/{media}', [CaseStudyController::class, 'destroyMedia'])->name('case-study-media.destroy');

    // Tags
    Route::post('/case-study-tags', [CaseStudyController::class, 'storeTag'])->name('case-study-tags.store');
    Route::delete('/case-study-tags/{tag}', [CaseStudyController::class, 'destroyTag'])->name('case-study-tags.destroy');

    // CRM Entity Links
    Route::post('/case-studies/{caseStudy}/link', [CaseStudyController::class, 'linkEntity'])->name('case-studies.link');
    Route::delete('/case-study-links/{link}', [CaseStudyController::class, 'unlinkEntity'])->name('case-study-links.destroy');
});
