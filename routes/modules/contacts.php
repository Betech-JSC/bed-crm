<?php

use App\Http\Controllers\ContactsController;
use App\Http\Controllers\OrganizationsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Contacts
    Route::get('contacts', [ContactsController::class, 'index'])->name('contacts');
    Route::get('contacts/create', [ContactsController::class, 'create'])->name('contacts.create');
    Route::post('contacts', [ContactsController::class, 'store'])->name('contacts.store');
    Route::get('contacts/{contact}/edit', [ContactsController::class, 'edit'])->name('contacts.edit');
    Route::put('contacts/{contact}', [ContactsController::class, 'update'])->name('contacts.update');
    Route::delete('contacts/{contact}', [ContactsController::class, 'destroy'])->name('contacts.destroy');
    Route::put('contacts/{contact}/restore', [ContactsController::class, 'restore'])->name('contacts.restore');

    // Organizations
    Route::get('organizations', [OrganizationsController::class, 'index'])->name('organizations');
    Route::get('organizations/create', [OrganizationsController::class, 'create'])->name('organizations.create');
    Route::post('organizations', [OrganizationsController::class, 'store'])->name('organizations.store');
    Route::get('organizations/{organization}/edit', [OrganizationsController::class, 'edit'])->name('organizations.edit');
    Route::put('organizations/{organization}', [OrganizationsController::class, 'update'])->name('organizations.update');
    Route::delete('organizations/{organization}', [OrganizationsController::class, 'destroy'])->name('organizations.destroy');
    Route::put('organizations/{organization}/restore', [OrganizationsController::class, 'restore'])->name('organizations.restore');
});
