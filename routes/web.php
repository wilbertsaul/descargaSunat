<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DescargaMasivaController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/descargas', [DescargaMasivaController::class, 'index'])->name('descargas.index');
    Route::get('/descargas/create', [DescargaMasivaController::class, 'create'])->name('descargas.create');
    Route::post('/descargas', [DescargaMasivaController::class, 'store'])->name('descargas.store');
    Route::get('/descargas/{descarga}', [DescargaMasivaController::class, 'show'])->name('descargas.show');
    Route::get('/descargas/{descarga}/estado', [DescargaMasivaController::class, 'estado'])->name('descargas.estado');
    Route::get('/descargas/{descarga}/zip', [DescargaMasivaController::class, 'descargarZip'])->name('descargas.zip');

    Route::get('/documentos/{documento}/descargar', [DocumentoController::class, 'descargar'])->name('documentos.descargar');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
