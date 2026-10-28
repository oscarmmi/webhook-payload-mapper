<?php

use App\Http\Controllers\WebhookMappingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebhookMappingController::class, 'index'])->name('home');
Route::post('/webhooks/map', [WebhookMappingController::class, 'store'])->name('webhooks.map');
