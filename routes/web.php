<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Coach\CoachController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/find-a-coach', [PageController::class, 'findACoach'])->name('find-a-coach');
Route::get('/become-a-coach', [PageController::class, 'becomeACoach'])->name('become-a-coach');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/coach-profile', [PageController::class, 'coachProfile'])->name('coach-profile');
Route::get('/player-dashboard', [PageController::class, 'playerDashboard'])->name('player-dashboard');
Route::get('/request-session', [PageController::class, 'requestSession'])->name('request-session');

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Coach portal
|--------------------------------------------------------------------------
*/
Route::prefix('coach')->name('coach.')->middleware(['auth', 'role:coach,admin'])->group(function () {
    Route::get('/schedule', [CoachController::class, 'schedule'])->name('schedule');
    Route::get('/dashboard', [CoachController::class, 'dashboard'])->name('dashboard');
    Route::get('/player-overview', [CoachController::class, 'playerOverview'])->name('player-overview');
    Route::get('/players/{player}', [CoachController::class, 'playerShow'])->name('players.show');
    Route::get('/add-report', [CoachController::class, 'addReport'])->name('add-report');
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
    Route::get('/bookings', [DashboardController::class, 'bookings'])->name('bookings');
    Route::get('/locations', [DashboardController::class, 'locations'])->name('locations');
    Route::get('/athletes', [DashboardController::class, 'athletes'])->name('athletes');
});
