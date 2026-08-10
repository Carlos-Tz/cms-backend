<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\StationController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API routes (prefix: /api/admin)
|--------------------------------------------------------------------------
|
| Private API consumed by the admin panel. Everything except login is
| protected by the JWT `api` guard.
|
*/

Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function (): void {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    Route::apiResource('sites', SiteController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('menus', MenuController::class);
    // Pages are structure too: the admin only publishes and unpublishes them.
    Route::apiResource('pages', PageController::class)->only(['index', 'show', 'update']);
    // Sections are structure: the admin edits content only, never creates or deletes them.
    Route::apiResource('sections', SectionController::class)->only(['index', 'show', 'update']);
    // No blog categories: the blueprint does not define any. Add them when asked for.
    Route::apiResource('blog-posts', BlogPostController::class);
    // Leads only come from the public forms: the panel reads and deletes them.
    Route::apiResource('leads', LeadController::class)->only(['index', 'show', 'destroy']);
    Route::apiResource('stations', StationController::class);
    Route::apiResource('media', MediaController::class);
});
