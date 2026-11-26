<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ClientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'home'])->name('home');
Route::get('/gyms', [CatalogController::class, 'gyms'])->name('gyms');
Route::get('/trainers', [CatalogController::class, 'trainers'])->name('trainers');
Route::get('/plans', [CatalogController::class, 'plans'])->name('plans');
Route::view('/about', 'about')->name('about');

Route::middleware('guest')->group(function () {
    Route::view('/login', 'login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:30,1');
    Route::view('/register', 'registration')->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/plans/continue', [CatalogController::class, 'continueToLogin'])->name('plans.continue');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function (Request $request) {
        return redirect()->route(match ($request->user()->role) {
            'admin' => 'admin',
            'trainer' => 'chat.index',
            default => 'cabinet',
        });
    })->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('role:client')->group(function () {
        Route::get('/cabinet', [ClientController::class, 'index'])->name('cabinet');
        Route::post('/memberships', [ClientController::class, 'submit'])->name('memberships.submit');
        Route::post('/slots/{slot}/book', [ClientController::class, 'book'])->name('bookings.book');
        Route::post('/bookings/{booking}/cancel', [ClientController::class, 'cancel'])->name('bookings.cancel');
        Route::post('/trainers/{trainer}/chat', [ChatController::class, 'open'])->name('chat.open');
    });

    Route::middleware('role:client,trainer')->prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::get('/{thread}', [ChatController::class, 'show'])->name('show');
        Route::post('/{thread}', [ChatController::class, 'send'])->name('send');
    });

    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('admin');
        Route::post('/applications/{application}', [AdminController::class, 'decide'])->name('admin.decide');
        Route::get('/catalog', [AdminController::class, 'catalog'])->name('admin.catalog');
        Route::post('/gyms/{gym?}', [AdminController::class, 'gym'])->name('admin.gym');
        Route::post('/trainers/{trainer?}', [AdminController::class, 'trainer'])->name('admin.trainer');
        Route::post('/plans/{plan?}', [AdminController::class, 'plan'])->name('admin.plan');
        Route::get('/slots', [AdminController::class, 'slots'])->name('admin.slots');
        Route::post('/slots/{slot?}', [AdminController::class, 'slot'])->name('admin.slot');
        Route::get('/slots/{slot}/roster', [AdminController::class, 'roster'])->name('admin.roster');
        Route::post('/slots/{slot}/bookings/{booking}', [AdminController::class, 'attendance'])->name('admin.attendance');
    });
});
