<?php

use App\Http\Controllers\API\Auth\LogoutController;
use App\Http\Controllers\API\ContactController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\SalePriceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ReplyController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\ReactionCoontroller;
use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\RegisterController;
use App\Http\Controllers\API\Auth\ResetPasswordController;

//register
Route::post('register', [RegisterController::class, 'register']);
Route::post('/verify-email', [RegisterController::class, 'VerifyEmail']);
Route::post('/resend-otp', [RegisterController::class, 'ResendOtp']);
//login
Route::post('login', [LoginController::class, 'Login']);
//social login
Route::post('socialLogin', [LoginController::class, 'socialLogin']);
//forgot password
Route::post('/forget-password', [ResetPasswordController::class, 'forgotPassword']);
Route::post('/verify-otp', [ResetPasswordController::class, 'VerifyOTP']);
Route::post('/reset-password', [ResetPasswordController::class, 'ResetPassword']);


Route::get('/fetch-review/latest', [ReviewController::class, 'fetchRecentReviews']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [LogoutController::class, 'logout']);
    //store location data
    Route::post('/store-review', [ReviewController::class, 'storeReview']);
    Route::post('/update-review/{id}', [ReviewController::class, 'updateReview']);
    Route::delete('/delete-review/{id}', [ReviewController::class, 'deleteReview']);
    Route::get('/fetch-review', [ReviewController::class, 'fetchReview']);

    //replay
    Route::get('/reviews/{reviewId}/replies', [ReplyController::class, 'index']);
    Route::post('/reply', [ReplyController::class, 'store']);
    Route::post('/update-reply/{id}', [ReplyController::class, 'update']);
    Route::post('/delete-reply/{id}', [ReplyController::class, 'delete']);


    //report
    Route::post('/store-report', [ReportController::class, 'store']);
    Route::post('/update-report/{id}', [ReportController::class, 'update']);
    Route::delete('/delete-report/{id}', [ReportController::class, 'delete']);

    Route::post('/reaction', [ReactionCoontroller::class, 'store']);
    Route::get('/reviews/{reviewId}/reactions/counts', [ReactionCoontroller::class, 'getCounts']);
    Route::get('/reviews/{reviewId}/user-reaction', [ReactionCoontroller::class, 'getUserReaction']);

    //replay
    Route::get('/reviews/{reviewId}/replies', [ReplyController::class, 'index']);
    Route::post('/reply', [ReplyController::class, 'store']);


    Route::post('/contact', [ContactController::class, 'store']);


    //profile
    Route::get('/profile/personal-info', [ProfileController::class, 'getPersonalInfo']);
    Route::post('/profile/personal-info/update', [ProfileController::class, 'updatePersonalInfo']);
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);
    Route::post('/profile/change-avatar', [ProfileController::class, 'changeAvatar']);
    Route::get('/profile/my-reviews', [ProfileController::class, 'getMyReviews']);
    Route::get('/profile/my-replies', [ProfileController::class, 'getMyReplies']);
    Route::get('/profile/my-reports', [ProfileController::class, 'getMyReports']);

});

Route::post('/sale-estimate', [SalePriceController::class, 'estimate']);
