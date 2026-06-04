<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\OrganizerApplicationController;
use App\Http\Controllers\API\SavedEventController;
use App\Http\Controllers\API\UserSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Handle all OPTIONS requests for CORS
Route::options('{any}', function () {
    return response()->json([], 200);
})->where('any', '.*');

// Public routes
Route::get('/register', function () {
    return response()->json([
        'message' => 'Use POST /api/register to create a new account.',
    ], 405);
});
Route::options('/register', function () {
    return response()->json([], 200);
});
Route::post('/register', [AuthController::class, 'register']);

Route::get('/login', function () {
    return response()->json([
        'message' => 'Use POST /api/login to sign in.',
    ], 405);
})->name('login');
Route::options('/login', function () {
    return response()->json([], 200);
});
Route::post('/login', [AuthController::class, 'login']);

// Social login routes
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::get('/auth/github', [AuthController::class, 'redirectToGitHub']);
Route::get('/auth/github/callback', [AuthController::class, 'handleGitHubCallback']);

// Public event routes
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);
Route::get('/events/{id}/reviews', [ReviewController::class, 'eventReviews']);

// Public review routes
Route::get('/reviews', [ReviewController::class, 'index']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user', [AuthController::class, 'updateProfile']);
    
    // Event routes (authenticated)
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{id}', [EventController::class, 'update']);
    Route::delete('/events/{id}', [EventController::class, 'destroy']);
    Route::get('/my-events', [EventController::class, 'myEvents']);
    
    // Booking routes
    Route::post('/events/{id}/book', [EventController::class, 'book']);
    Route::get('/my-bookings', [EventController::class, 'myBookings']);
    Route::put('/bookings/{id}/cancel', [EventController::class, 'cancelBooking']);
    Route::put('/bookings/{id}/quantity', [EventController::class, 'updateQuantity']);

    // Review routes
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/my-reviews', [ReviewController::class, 'userReviews']);

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // Organizer application routes
    Route::get('/organizer-application/status', [OrganizerApplicationController::class, 'status']);
    Route::post('/organizer-application', [OrganizerApplicationController::class, 'store']);
    Route::get('/organizer-applications', [OrganizerApplicationController::class, 'index']);
    Route::put('/organizer-applications/{id}/status', [OrganizerApplicationController::class, 'updateStatus']);

    // Saved events routes
    Route::get('/saved-events', [SavedEventController::class, 'index']);
    Route::post('/events/{id}/save', [SavedEventController::class, 'toggle']);
    Route::get('/events/{id}/saved-status', [SavedEventController::class, 'status']);

    // User settings routes
    Route::get('/settings', [UserSettingsController::class, 'show']);
    Route::put('/settings', [UserSettingsController::class, 'update']);

    // Admin routes (kaoutarsaydi0@gmail.com only)
    Route::prefix('admin')->group(function () {
        Route::get('/stats',                        [\App\Http\Controllers\API\AdminController::class, 'stats']);
        Route::get('/overview',                     [\App\Http\Controllers\API\AdminController::class, 'overview']);
        Route::get('/recent-events',                [\App\Http\Controllers\API\AdminController::class, 'recentEvents']);
        Route::get('/recent-users',                 [\App\Http\Controllers\API\AdminController::class, 'recentUsers']);
        Route::get('/recent-bookings',              [\App\Http\Controllers\API\AdminController::class, 'recentBookings']);
        Route::get('/recent-reviews',               [\App\Http\Controllers\API\AdminController::class, 'recentReviews']);
        Route::get('/activity',                     [\App\Http\Controllers\API\AdminController::class, 'recentActivity']);
        Route::get('/organizer-applications',       [\App\Http\Controllers\API\AdminController::class, 'organizerApplications']);
        Route::put('/organizer-applications/{id}/approve', [\App\Http\Controllers\API\AdminController::class, 'approveApplication']);
        Route::put('/organizer-applications/{id}/reject',  [\App\Http\Controllers\API\AdminController::class, 'rejectApplication']);
        Route::get('/users',                        [\App\Http\Controllers\API\AdminController::class, 'allUsers']);
        Route::delete('/users/{id}',                [\App\Http\Controllers\API\AdminController::class, 'deleteUser']);
    });
});