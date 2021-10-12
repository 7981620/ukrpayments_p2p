<?php

use Agenta\UkrpaymentsP2p\Controllers\p2pController;
use Illuminate\Support\Facades\Route;

Route::get('/p2p', [p2pController::class, 'index'])->name('p2p.index');
Route::post('/p2p', [p2pController::class, 'store'])->name('p2p.store');
Route::get('/p2p/3ds', [p2pController::class, 'formRedirect'])->name('p2p.redirect');
Route::post('/p2p-status', [p2pController::class, 'callback'])->name('p2p.callback');
Route::get('/p2p-status', [p2pController::class, 'getA2CStatus'])->name('p2p.status');
