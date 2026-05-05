<?php

use App\Http\Controllers\Api\V1\AppointmentController as V1AppointmentController;
use App\Http\Controllers\Api\V1\AuthController as V1AuthController;
use App\Http\Controllers\Api\V1\ContactController as V1ContactController;
use App\Http\Controllers\Api\V1\DoctorController as V1DoctorController;
use App\Http\Controllers\Api\V1\EmailVerificationController as V1EmailVerificationController;
use App\Http\Controllers\Api\V1\LoyaltyController as V1LoyaltyController;
use App\Http\Controllers\Api\V1\LoyaltyTierController as V1LoyaltyTierController;
use App\Http\Controllers\Api\V1\PhoneVerificationController as V1PhoneVerificationController;
use App\Http\Controllers\Api\V1\ProfileController as V1ProfileController;
use App\Http\Controllers\Api\V1\ReviewController as V1ReviewController;
use App\Http\Controllers\Api\V1\ServiceController as V1ServiceController;
use App\Http\Controllers\Api\V1\SlotController as V1SlotController;
use App\Http\Controllers\Api\V1\SmartIdVerificationController as V1SmartIdVerificationController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('user', [AuthController::class, 'user'])->middleware('auth:sanctum');
    Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('email/resend', [AuthController::class, 'resendVerification'])
        ->middleware(['auth:sanctum', 'throttle:6,1']);
});

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register', [V1AuthController::class, 'register'])->name('register');
        Route::post('login', [V1AuthController::class, 'login'])->name('login');
        Route::post('logout', [V1AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
    });

    Route::get('doctors', [V1DoctorController::class, 'index'])->name('doctors.index');
    Route::get('doctors/{doctor}', [V1DoctorController::class, 'show'])->name('doctors.show');
    Route::get('doctors/{doctor}/services', [V1DoctorController::class, 'services'])->name('doctors.services.index');
    Route::get('doctors/{doctor}/slots', [V1SlotController::class, 'index'])->name('doctors.slots.index');
    Route::get('services', [V1ServiceController::class, 'index'])->name('services.index');
    Route::get('tiers', [V1LoyaltyTierController::class, 'index'])->name('tiers.index');
    Route::get('reviews', [V1ReviewController::class, 'index'])->name('reviews.index');
    Route::post('contact', [V1ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('appointments', [V1AppointmentController::class, 'index'])->name('appointments.index');
        Route::post('appointments', [V1AppointmentController::class, 'store'])->name('appointments.store');
        Route::post('appointments/preview', [V1AppointmentController::class, 'preview'])->name('appointments.preview');
        Route::post('appointments/request', [V1AppointmentController::class, 'requestStore'])->name('appointments.request');
        Route::get('appointments/{appointment}', [V1AppointmentController::class, 'show'])->name('appointments.show');
        Route::delete('appointments/{appointment}', [V1AppointmentController::class, 'destroy'])->name('appointments.destroy');
        Route::patch('appointments/{appointment}/reschedule', [V1AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
        Route::get('loyalty', [V1LoyaltyController::class, 'show'])->name('loyalty.show');
        Route::post('reviews', [V1ReviewController::class, 'store'])->name('reviews.store');

        Route::get('profile', [V1ProfileController::class, 'show'])->name('profile.show');
        Route::patch('profile', [V1ProfileController::class, 'update'])->name('profile.update');
        Route::patch('profile/password', [V1ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::post('email/verification-notification', [V1EmailVerificationController::class, 'store'])->middleware('throttle:1,1')->name('email.verification.send');
        Route::post('phone/send-otp', [V1PhoneVerificationController::class, 'sendOtp'])->name('phone.send-otp');
        Route::post('phone/verify-otp', [V1PhoneVerificationController::class, 'verifyOtp'])->name('phone.verify-otp');
        Route::post('smart-id/initiate', [V1SmartIdVerificationController::class, 'initiate'])->middleware('throttle:3,5')->name('smart-id.initiate');
        Route::get('smart-id/poll/{token}', [V1SmartIdVerificationController::class, 'poll'])->name('smart-id.poll');
    });
});
