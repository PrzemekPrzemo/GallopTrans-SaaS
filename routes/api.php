<?php

use App\Http\Controllers\ApiInquiryController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['ok' => true, 'service' => 'galloptrans']);

// Publiczne API zapytań ofertowych (do widgetu osadzanego na WWW klienta).
// CORS jest ustawiony globalnie (config/cors.php).
Route::post('/o/{slug}/inquiry', [ApiInquiryController::class, 'store'])
    ->name('api.inquiry.store');
