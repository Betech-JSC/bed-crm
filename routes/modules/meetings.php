<?php

use App\Http\Controllers\MeetingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Meetings CRUD
    Route::get('meetings', [MeetingController::class, 'index'])->name('meetings');
    Route::get('meetings/create', [MeetingController::class, 'create'])->name('meetings.create');
    Route::post('meetings', [MeetingController::class, 'store'])->name('meetings.store');
    Route::get('meetings/{meeting}/edit', [MeetingController::class, 'edit'])->name('meetings.edit');
    Route::put('meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
    Route::delete('meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');

    // Meeting room & actions
    Route::get('meetings/{roomCode}/room', [MeetingController::class, 'room'])->name('meetings.room');
    Route::get('meetings/{meeting}/recap', [MeetingController::class, 'recap'])->name('meetings.recap');
    Route::post('meetings/{meeting}/start', [MeetingController::class, 'start'])->name('meetings.start');
    Route::post('meetings/{meeting}/end', [MeetingController::class, 'end'])->name('meetings.end');
    Route::post('meetings/{meeting}/recording', [MeetingController::class, 'saveRecording'])->name('meetings.recording');
    Route::post('meetings/{meeting}/notes', [MeetingController::class, 'saveNotes'])->name('meetings.notes');
    Route::post('meetings/{meeting}/recap-generate', [MeetingController::class, 'generateRecap'])->name('meetings.recap-generate');
    Route::post('meetings/{meeting}/cancel', [MeetingController::class, 'cancel'])->name('meetings.cancel');
    Route::post('meetings/{meeting}/duplicate', [MeetingController::class, 'duplicate'])->name('meetings.duplicate');
});
