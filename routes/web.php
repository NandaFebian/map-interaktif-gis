<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarkerController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/map', function () {
    return view('map');
})->middleware(['auth'])->name('map');

// Simpan marker baru
Route::post('/markers', [MarkerController::class, 'store']);

// Ambil semua marker
Route::get('/allMarkers', [MarkerController::class, 'index']);

// Hapus marker
Route::delete('/delMarkers/{marker}', [MarkerController::class, 'destroy']);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';