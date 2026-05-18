<?php

use Illuminate\Support\Facades\Route;

// Publiczne API dla widgetu zapytań ofertowych z WWW klienta SaaS.
// Endpoint: POST /api/inquiry  (CORS, rate-limit, tenant po slug/api_key)
// TODO: dodać ApiInquiryController w kolejnej iteracji.

Route::get('/health', fn () => ['ok' => true, 'service' => 'galloptrans']);
