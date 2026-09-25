<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Coach\CoachController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SessionRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/media/{path}', [\App\Http\Controllers\MediaController::class, 'show'])
    ->where('path', '.*')
    ->name('media.show');
Route::get('/download/{path}', [\App\Http\Controllers\MediaController::class, 'download'])
    ->where('path', '.*')
    ->name('media.download');
Route::get('/find-a-coach', [PageController::class, 'findACoach'])->name('find-a-coach');
Route::get('/become-a-coach', [PageController::class, 'becomeACoach'])->name('become-a-coach');
Route::post('/become-a-coach', [PageController::class, 'submitBecomeACoach'])->name('become-a-coach.submit');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'submitContact'])->name('contact.submit');
Route::get('/coach-profile', fn () => redirect()->route('find-a-coach'));
Route::get('/coaches/{coach}', [PageController::class, 'coachProfile'])->name('coach-profile');
Route::get('/api/coaches/{coach}/slots', [\App\Http\Controllers\BookingController::class, 'slots'])->name('coaches.slots');

Route::middleware('auth')->group(function () {
    Route::middleware('role:athlete')->group(function () {
        Route::get('/player-dashboard', [PageController::class, 'playerDashboard'])->name('player-dashboard');
        Route::get('/book/{coach}', [PageController::class, 'bookCoach'])->name('book-coach');
        Route::get('/request-session', [PageController::class, 'requestSession'])->name('request-session');
        Route::post('/api/session-requests', [SessionRequestController::class, 'store'])->name('session-requests.store');
        Route::post('/api/session-requests/{reference}/join', [SessionRequestController::class, 'join'])->name('session-requests.join');
        Route::post('/api/session-requests/{reference}/cancel', [SessionRequestController::class, 'cancel'])->name('session-requests.cancel');
        Route::post('/api/bookings', [\App\Http\Controllers\BookingController::class, 'store'])->name('bookings.store');
    });

    Route::get('/api/session-requests', [SessionRequestController::class, 'index'])->name('session-requests.index');
    Route::get('/api/session-requests/{reference}', [SessionRequestController::class, 'show'])->name('session-requests.show');

    Route::middleware('role:coach')->group(function () {
        Route::post('/api/session-requests/{reference}/accept', [SessionRequestController::class, 'accept'])->name('session-requests.accept');
        Route::post('/api/session-requests/{reference}/decline', [SessionRequestController::class, 'decline'])->name('session-requests.decline');
        Route::patch('/api/session-requests/{reference}', [SessionRequestController::class, 'update'])->name('session-requests.update');
    });
});

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Coach portal
|--------------------------------------------------------------------------
*/
Route::prefix('coach')->name('coach.')->middleware(['auth', 'role:coach'])->group(function () {
    Route::get('/schedule', [CoachController::class, 'schedule'])->name('schedule');
    Route::post('/schedule/availability', [CoachController::class, 'storeAvailability'])->name('schedule.availability.store');
    Route::patch('/schedule/availability/{slot}', [CoachController::class, 'updateAvailability'])->name('schedule.availability.update');
    Route::delete('/schedule/availability/{slot}', [CoachController::class, 'destroyAvailability'])->name('schedule.availability.destroy');
    Route::post('/schedule/sessions', [CoachController::class, 'storeSession'])->name('schedule.sessions.store');
    Route::get('/dashboard', [CoachController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [CoachController::class, 'profile'])->name('profile');
    Route::get('/api/status', [CoachController::class, 'status'])->name('status');
    Route::put('/profile', [CoachController::class, 'updateProfile'])->name('profile.update');
    Route::get('/player-overview', [CoachController::class, 'playerOverview'])->name('player-overview');
    Route::get('/players/{player}', [CoachController::class, 'playerShow'])->name('players.show');
    Route::post('/players/{player}/videos', [CoachController::class, 'storeVideo'])->name('players.videos.store');
    Route::delete('/players/{player}/videos/{video}', [CoachController::class, 'destroyVideo'])->name('players.videos.destroy');
    Route::get('/add-report', [CoachController::class, 'addReport'])->name('add-report');
    Route::post('/add-report/generate', [CoachController::class, 'generateReport'])->name('add-report.generate');
    Route::post('/add-report', [CoachController::class, 'storeReport'])->name('add-report.store');
});

/*
|--------------------------------------------------------------------------
| Admin dashboard
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/schedule', [DashboardController::class, 'schedule'])->name('schedule');
    Route::get('/coaches', [DashboardController::class, 'coaches'])->name('coaches');
    Route::post('/coaches', [DashboardController::class, 'storeCoach'])->name('coaches.store');
    Route::patch('/coaches/{coach}', [DashboardController::class, 'updateCoach'])->name('coaches.update');
    Route::patch('/coaches/{coach}/status', [DashboardController::class, 'updateCoachStatus'])->name('coaches.status');
    Route::patch('/coaches/{coach}/approval', [DashboardController::class, 'updateCoachApproval'])->name('coaches.approval');
    Route::get('/bookings', [DashboardController::class, 'bookings'])->name('bookings');
    Route::get('/locations', [DashboardController::class, 'locations'])->name('locations');
    Route::post('/locations', [DashboardController::class, 'storeLocation'])->name('locations.store');
    Route::delete('/locations/{location}', [DashboardController::class, 'destroyLocation'])->name('locations.destroy');
    Route::get('/athletes', [DashboardController::class, 'athletes'])->name('athletes');
    Route::get('/messages', [DashboardController::class, 'messages'])->name('messages');
    Route::patch('/messages/{message}/read', [DashboardController::class, 'markMessageRead'])->name('messages.read');
});
