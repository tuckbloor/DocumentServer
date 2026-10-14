<?php

use App\Http\Controllers\DocumentServer\DashboardController;
use App\Http\Controllers\DocumentServer\DocumentCategoryController;
use App\Http\Controllers\DocumentServer\DocumentController;
use App\Http\Controllers\DocumentServer\DocumentDirectoryController;
use App\Http\Controllers\DocumentServer\DocumentFavouriteController;
use App\Http\Controllers\DocumentServer\DocumentFavouritesPageController;
use App\Http\Controllers\DocumentServer\DocumentShareController;
use App\Http\Controllers\DocumentServer\StandaloneDavController;
use App\Http\Controllers\DocumentServer\StandaloneLaunchController;
use App\Http\Controllers\DocumentServer\StandaloneStatusController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Document Server pages and actions: authenticated Laravel users only.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/document-server/users', [DocumentDirectoryController::class, 'users'])
        ->name('document-server.users');
    Route::get('/document-server/activity', [DocumentDirectoryController::class, 'activity'])
        ->name('document-server.activity');

    // Categories
    Route::get('/document-server/categories', [DocumentCategoryController::class, 'index'])
        ->name('document-server.categories');
    Route::post('/document-server/categories', [DocumentCategoryController::class, 'store'])
        ->name('document-server.categories.store');
    Route::patch('/document-server/categories/{category}', [DocumentCategoryController::class, 'update'])
        ->whereNumber('category')->name('document-server.categories.update');
    Route::delete('/document-server/categories/{category}', [DocumentCategoryController::class, 'destroy'])
        ->whereNumber('category')->name('document-server.categories.delete');

    // Favourites
    Route::get('/document-server/favourites', [DocumentFavouritesPageController::class, 'index'])
        ->name('document-server.favourites');

    // Documents and revisions
    Route::post('/documents', [DocumentController::class, 'upload'])->name('documents.upload');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
        ->whereNumber('document')->name('documents.download');
    Route::get('/documents/{document}/versions', [DocumentController::class, 'versions'])
        ->whereNumber('document')->name('documents.versions');
    Route::get('/documents/{document}/versions/{version}/download', [DocumentController::class, 'downloadVersion'])
        ->whereNumber('document')->whereNumber('version')->name('documents.versions.download');
    Route::post('/documents/{document}/revisions', [DocumentController::class, 'revision'])
        ->whereNumber('document')->name('documents.revisions');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])
        ->whereNumber('document')->name('documents.delete');
    Route::post('/documents/{document}/restore', [DocumentController::class, 'restore'])
        ->whereNumber('document')->name('documents.restore');

    // Sharing and favourites
    Route::post('/documents/{document}/shares', [DocumentShareController::class, 'store'])
        ->whereNumber('document')->name('documents.shares.store');
    Route::delete('/documents/{document}/shares/{recipient}', [DocumentShareController::class, 'destroy'])
        ->whereNumber('document')->whereNumber('recipient')->name('documents.shares.destroy');
    Route::post('/documents/{document}/favourite', [DocumentFavouriteController::class, 'toggle'])
        ->whereNumber('document')->name('documents.favourite');
});

// A logged-in user starts/stops an editing session and checks the saved revision.
Route::middleware(['auth', 'verified', 'throttle:30,1'])->group(function () {
    Route::post('/documents/{document}/standalone/open', [StandaloneLaunchController::class, 'open'])
        ->whereNumber('document')->name('documents.standalone.open');
    Route::post('/documents/{document}/standalone/close', [StandaloneLaunchController::class, 'close'])
        ->whereNumber('document')->name('documents.standalone.close');
    Route::get('/documents/{document}/standalone/status', [StandaloneStatusController::class, 'show'])
        ->whereNumber('document')->name('documents.standalone.status');
});

// WebDAV clients use a scoped, short-lived editing token rather than browser cookies.
// This endpoint must validate the token and document permissions on every request.
Route::match([
    'GET', 'HEAD', 'OPTIONS', 'PROPFIND', 'PROPPATCH', 'PUT', 'LOCK', 'UNLOCK', 'REPORT',
], '/standalone-dav/{token}/{path?}', [StandaloneDavController::class, 'handle'])
    ->where('token', '[A-Za-z0-9_-]{43}')
    ->where('path', '.*')
    ->withoutMiddleware([PreventRequestForgery::class])
    ->middleware('throttle:120,1')
    ->name('document-server.standalone-dav');

require __DIR__.'/settings.php';
