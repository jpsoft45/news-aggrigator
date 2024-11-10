<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [App\Http\Controllers\RegisterController::class, 'register']);
Route::post('/login', [App\Http\Controllers\LoginController::class, 'login']);
// Route::post('/password-reset', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.reset');

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/logout', [App\Http\Controllers\LoginController::class, 'logout']);
    Route::get('/feed', [ArticleController::class, 'personalizedFeed']);
    Route::group(['prefix' => 'articles'], function () {
        Route::get('/', [App\Http\Controllers\ArticleController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\ArticleController::class, 'show']);
    });
    Route::group(['prefix' => 'preferences'], function () {
        Route::get('/', [App\Http\Controllers\PreferenceController::class, 'index']);
        Route::post('/', [App\Http\Controllers\PreferenceController::class, 'store']);
    });
    Route::group(['prefix' => 'sources'], function () {
        Route::get('/', [App\Http\Controllers\SourceController::class, 'index']);
    });
});


