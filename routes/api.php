<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\QuestionController;
use App\Http\Controllers\API\SocialiteController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::group([
    'prefix' => '/v1/console',
    'middleware' => 'api',

], function () {
    Route::get('countries', [AuthController::class, 'getCountries']);
    Route::get('topics', [AuthController::class, 'getTopics']);
    Route::post('login', [AuthController::class, 'login'])->name('api-login');
    Route::post('register', [AuthController::class, 'register'])->name('api-register');
    Route::post('auth/{provider}', [SocialiteController::class, 'redirectToProvider'])->name('socialite.auth');
    Route::get('auth/{provider}/callback', [SocialiteController::class, 'handleCallback'])->name('socialite.callback');
    Route::post('auth/user/complete', [AuthController::class, 'completeRegistration'])->middleware(['auth:api']);
    Route::post('send/verification/email', [AuthController::class, 'resentVerificationEmail'])->middleware(['auth:api']);
    Route::post('verify/email', [AuthController::class, 'verifyEmail'])->name('verification.verify');

    // Routes under the middleware
    Route::group([
        'middleware' => ['auth:api']
    ], function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('api-logout');
        Route::get('users/profile', [UserController::class, 'userProfile'])->name('api-user-profile');
        Route::post('questions', [QuestionController::class, 'store'])->name('api-questions-creata');
    });
});

