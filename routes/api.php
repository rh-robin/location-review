<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ReplyController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\LocationController;
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
//forgot password
Route::post('/forget-password', [ResetPasswordController::class, 'forgotPassword']);
Route::post('/verify-otp', [ResetPasswordController::class, 'VerifyOTP']);
Route::post('/reset-password', [ResetPasswordController::class, 'ResetPassword']);
Route::middleware('auth:sanctum')->group(function () {
    //store location data
    Route::post('/store-location', [LocationController::class, 'StoreLocation']);
    Route::post('/store-review', [ReviewController::class, 'store']);
    Route::post('/store-report', [ReportController::class, 'store']);
    Route::get('/fetch-review', [ReviewController::class, 'fetchReview']);

    Route::post('/reaction', [ReactionCoontroller::class, 'store']);
    Route::get('/reviews/{reviewId}/reactions/counts', [ReactionCoontroller::class, 'getCounts']);
    Route::get('/reviews/{reviewId}/user-reaction', [ReactionCoontroller::class, 'getUserReaction']);

    //replay
    Route::get('/reviews/{reviewId}/replies', [ReplyController::class, 'index']);
    Route::post('/replies', [ReplyController::class, 'store']);
});
