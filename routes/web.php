<?php

use App\Http\Controllers\Admin\DashboardController;
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
Route::get('/login', [PageController::class, 'login'])->name('login');
Route::get('/coach-profile', [PageController::class, 'coachProfile'])->name('coach-profile');
Route::get('/player-dashboard', [PageController::class, 'playerDashboard'])->name('player-dashboard');
Route::get('/request-session', [PageController::class, 'requestSession'])->name('request-session');

/*
|--------------------------------------------------------------------------
| Coach portal (subscription + auth to be added next)
|--------------------------------------------------------------------------
*/
Route::prefix('coach')->name('coach.')->group(function () {
    Route::get('/schedule', [CoachController::class, 'schedule'])->name('schedule');
    Route::get('/dashboard', [CoachController::class, 'dashboard'])->name('dashboard');
    Route::get('/player-overview', [CoachController::class, 'playerOverview'])->name('player-overview');
    Route::get('/players/{player}', [CoachController::class, 'playerShow'])->name('players.show');
    Route::get('/add-report', [CoachController::class, 'addReport'])->name('add-report');
});

/*
|--------------------------------------------------------------------------
| Admin dashboard (auth to be added next)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/schedule', [DashboardController::class, 'schedule'])->name('schedule');
    Route::get('/coaches', [DashboardController::class, 'coaches'])->name('coaches');
    Route::get('/bookings', [DashboardController::class, 'bookings'])->name('bookings');
    Route::get('/locations', [DashboardController::class, 'locations'])->name('locations');
    Route::get('/athletes', [DashboardController::class, 'athletes'])->name('athletes');
});
