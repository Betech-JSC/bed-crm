<?php

use App\Http\Controllers\UsersController;
use App\Http\Controllers\AccountSettingsController;
use App\Http\Controllers\SMTPSettingsController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\ImagesController;
use App\Http\Controllers\AiProvidersController;
use App\Http\Controllers\SocialPlatformSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Users
    Route::get('users', [UsersController::class, 'index'])->name('users');
    Route::get('users/create', [UsersController::class, 'create'])->name('users.create');
    Route::post('users', [UsersController::class, 'store'])->name('users.store');
    Route::get('users/{user}/edit', [UsersController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [UsersController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UsersController::class, 'destroy'])->name('users.destroy');
    Route::put('users/{user}/restore', [UsersController::class, 'restore'])->name('users.restore');

    // Account Settings
    Route::get('account-settings', [AccountSettingsController::class, 'index'])->name('account-settings.index');
    Route::put('account-settings', [AccountSettingsController::class, 'update'])->name('account-settings.update');
    Route::delete('account-settings/logo', [AccountSettingsController::class, 'removeLogo'])->name('account-settings.logo.remove');
    Route::delete('account-settings/favicon', [AccountSettingsController::class, 'removeFavicon'])->name('account-settings.favicon.remove');
    Route::post('account-settings/seed', [AccountSettingsController::class, 'seedDefaults'])->name('account-settings.seed');
    Route::get('account-settings/config/{group}', [AccountSettingsController::class, 'getConfigGroup'])->name('account-settings.config.get');
    Route::put('account-settings/config/{group}', [AccountSettingsController::class, 'updateConfigGroup'])->name('account-settings.config.update');

    // SMTP Settings
    Route::get('smtp-settings', [SMTPSettingsController::class, 'index'])->name('smtp-settings.index');
    Route::post('smtp-settings', [SMTPSettingsController::class, 'store'])->name('smtp-settings.store');
    Route::post('smtp-settings/test', [SMTPSettingsController::class, 'test'])->name('smtp-settings.test');

    // Social Platform Settings (OAuth Configuration)
    Route::get('social-platforms', [SocialPlatformSettingsController::class, 'index'])->name('social-platforms.index');
    Route::post('social-platforms', [SocialPlatformSettingsController::class, 'store'])->name('social-platforms.store');
    Route::post('social-platforms/{platform}/toggle', [SocialPlatformSettingsController::class, 'toggle'])->name('social-platforms.toggle');
    Route::delete('social-platforms/{platform}', [SocialPlatformSettingsController::class, 'destroy'])->name('social-platforms.destroy');
    Route::get('api/social-platforms/{platform}/auth-url', [SocialPlatformSettingsController::class, 'getAuthUrl'])->name('social-platforms.auth-url');

    // Permissions & Roles
    Route::resource('permissions', PermissionsController::class);
    Route::resource('roles', RolesController::class);

    // Files
    Route::get('files', [FilesController::class, 'index'])->name('files');
    Route::get('files/create', [FilesController::class, 'create'])->name('files.create');
    Route::post('files', [FilesController::class, 'store'])->name('files.store');
    Route::get('files/{file}', [FilesController::class, 'show'])->name('files.show');
    Route::get('files/{file}/edit', [FilesController::class, 'edit'])->name('files.edit');
    Route::put('files/{file}', [FilesController::class, 'update'])->name('files.update');
    Route::delete('files/{file}', [FilesController::class, 'destroy'])->name('files.destroy');
    Route::get('files/{file}/download', [FilesController::class, 'download'])->name('files.download');
    Route::get('files/{file}/preview', [FilesController::class, 'preview'])->name('files.preview');

    // AI Providers
    Route::get('ai-providers', [AiProvidersController::class, 'index'])->name('ai-providers.index');
    Route::post('ai-providers', [AiProvidersController::class, 'store'])->name('ai-providers.store');
    Route::post('ai-providers/{provider}/test', [AiProvidersController::class, 'test'])->name('ai-providers.test');
    Route::post('ai-providers/{provider}/default', [AiProvidersController::class, 'setDefault'])->name('ai-providers.set-default');
    Route::post('ai-providers/{provider}/toggle', [AiProvidersController::class, 'toggle'])->name('ai-providers.toggle');
    Route::delete('ai-providers/{provider}', [AiProvidersController::class, 'destroy'])->name('ai-providers.destroy');

    // Language switching
    Route::get('lang/{locale}', function ($locale) {
        if (in_array($locale, ['vi', 'en'])) {
            session(['locale' => $locale]);
            app()->setLocale($locale);
            return redirect(url()->previous() ?: route('dashboard'));
        }
        return redirect()->back();
    })->name('lang.switch');
});

// Images (public)
Route::get('/img/{path}', [ImagesController::class, 'show'])->where('path', '.*')->name('image');

