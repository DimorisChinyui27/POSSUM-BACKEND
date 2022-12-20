<?php

use App\Http\Controllers\API\AnswerController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\QuestionController;
use App\Http\Controllers\API\ResourceController;
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
    Route::get('countries', [ResourceController::class, 'getCountries']);
    Route::get('topics', [ResourceController::class, 'getTopics']);
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
        Route::post('summarize', [ResourceController::class, 'summarize']);
        Route::post('translatetext', [ResourceController::class, 'translate']);
        Route::post('logout', [AuthController::class, 'logout'])->name('api-logout');
        Route::get('users/profile', [UserController::class, 'userProfile'])->name('api-user-profile');

        Route::group([
            'middleware' => ['complete.registration']
        ], function () {
            // USERS
            Route::get('users/search', [UserController::class, 'searchUser']);
            Route::get('users/ranking', [UserController::class, 'index']);
            Route::get('users/edit', [UserController::class, 'edit']);
            Route::post('users/topics/remove', [UserController::class, 'removeTopic']);
            Route::post('users/topics/add', [UserController::class, 'addTopic']);
            Route::post('users/update', [UserController::class, 'update']);
            Route::post('users/update/avatar', [UserController::class, 'updateAvatar']);
            Route::get('users/{username}/answers', [UserController::class, 'getAnswers']);
            Route::get('users/{username}/questions', [UserController::class, 'getQuestions']);
            Route::get('users/{username}/show', [UserController::class, 'show']);

            // QUESTIONS
            Route::get('questions/search', [QuestionController::class, 'searchQuestion']);
            Route::get('questions/recent', [QuestionController::class, 'recentQuestion']);
            Route::get('questions', [QuestionController::class, 'index'])->name('qpi=questions-index');
            Route::post('questions', [QuestionController::class, 'store'])
                ->name('api-questions-create');
            Route::get('questions/{id}', [QuestionController::class, 'show'])
                ->name('api-questions-show');
            Route::post('questions/{id}/update', [QuestionController::class, 'update'])
                ->name('api-questions-update');
            Route::post('questions/{id}/destroy', [QuestionController::class, 'destroy'])
                ->name('api-questions-destroy');
            Route::post('questions/{question_id}/gift', [QuestionController::class, 'addGift'])
                ->name('api-questions-gift-add');
            Route::get('questions/{question_id}/answers', [QuestionController::class, 'getAnswers'])
                ->name('api-questions-answers');
            Route::post('questions/{question_id}/gift/remove', [QuestionController::class, 'removeGift'])
                ->name('api-questions-gift-remove');
            Route::post('questions/{question_id}/vote', [QuestionController::class, 'vote'])
                ->name('api-question-vote');
            Route::get('questions/{question_id}/comments', [QuestionController::class, 'getComments'])
                ->name('api-questions-comments');
            Route::post('questions/{question_id}/comments/add', [QuestionController::class, 'addComment'])
                ->name('api-question-add-comment');

            // ANSWER
            Route::post('answers', [AnswerController::class, 'store'])->name('api.answer.store');
            Route::get('answers/{id}', [AnswerController::class, 'show'])->name('api.answer.show');
            Route::post('answers/{id}/update', [AnswerController::class, 'update'])->name('api.answer.update');
            Route::post('answers/{id}/vote', [AnswerController::class, 'vote']);
            Route::post('answers/{id}/delete', [AnswerController::class, 'destroy']);
            Route::post('answers/{id}/satisfy', [AnswerController::class, 'satisfy']);
            Route::post('answers/{id}/add/comment', [AnswerController::class, 'addComment']);
            Route::get('answers/{id}/comments', [AnswerController::class, 'getComments']);
            Route::post('answers/{id}/send-gift', [AnswerController::class, 'sendGift'])->name('api.answer.tip');

        });
    });
});

