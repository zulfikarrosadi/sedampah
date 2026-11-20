<?php

use App\Http\Controllers\Api\MidtransWebhookController;
use App\Http\Controllers\Petugas\ScannerController;
use Illuminate\Support\Facades\Route;

Route::post('/midtrans/notification', [MidtransWebhookController::class, 'handle'])
  ->name('api.midtrans.notification');

Route::middleware(['web', 'auth', 'role:petugas,admin'])->post('/tickets/verify', [ScannerController::class, 'verify'])
  ->name('api.tickets.verify');
