<?php

use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('rooms.index');
});

// Public routes
Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
Route::get('/rooms/{id}', [RoomController::class, 'show'])->name('rooms.show');

// Authenticated user routes
Route::middleware(['auth'])->group(function () {
    // Reservations
    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::post('/reservations/{id}/cancel-request', [ReservationController::class, 'requestCancel'])
        ->name('reservations.request-cancel');
});

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Room management
    Route::get('/rooms', [AdminRoomController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/create', [AdminRoomController::class, 'create'])->name('rooms.create');
    Route::post('/rooms', [AdminRoomController::class, 'store'])->name('rooms.store');
    Route::get('/rooms/{id}/edit', [AdminRoomController::class, 'edit'])->name('rooms.edit');
    Route::put('/rooms/{id}', [AdminRoomController::class, 'update'])->name('rooms.update');
    Route::post('/rooms/{id}/toggle-active', [AdminRoomController::class, 'toggleActive'])
        ->name('rooms.toggle-active');

    // Reservation management
    Route::get('/reservations/cancel-requests', [AdminReservationController::class, 'cancelRequests'])
        ->name('reservations.cancel-requests');
    Route::post('/reservations/{id}/approve-cancel', [AdminReservationController::class, 'approveCancellation'])
        ->name('reservations.approve-cancel');
    Route::post('/reservations/{id}/reject-cancel', [AdminReservationController::class, 'rejectCancellation'])
        ->name('reservations.reject-cancel');
    Route::post('/reservations/{id}/cancel', [AdminReservationController::class, 'cancel'])
        ->name('reservations.cancel');
});
