<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\CalculatorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Publiczna oferta (token) - bez logowania
Route::get('/q/{token}', [QuoteController::class, 'public'])->name('quotes.public');

// Onboarding (po rejestracji) - tu user JESZCZE nie ma organization
Route::middleware('auth')->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'create'])->name('onboarding.create');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

// Strefa aplikacji - tylko z organizacją
Route::middleware(['auth', 'ensure.org'])->group(function () {

    // Billing (dostępne nawet bez subskrypcji - bo to TUTAJ ją kupujemy)
    Route::get('/billing/plans',  [BillingController::class, 'plans'])->name('billing.plans');
    Route::get('/billing/checkout/{plan}', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');
    Route::get('/billing/portal',  [BillingController::class, 'portal'])->name('billing.portal');

    // Profil użytkownika
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Wszystko poniżej wymaga aktywnej subskrypcji / trialu
    Route::middleware('ensure.subscribed')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Kalkulator tras
        Route::prefix('calculator')->name('calculator.')->group(function () {
            Route::get('/',                 [CalculatorController::class, 'index'])->name('index');
            Route::get('/geocode',          [CalculatorController::class, 'geocode'])->name('geocode');
            Route::post('/reverse-geocode', [CalculatorController::class, 'reverseGeocode'])->name('reverse-geocode');
            Route::post('/route',           [CalculatorController::class, 'route'])->name('route');
            Route::post('/estimate-tolls',  [CalculatorController::class, 'estimateTolls'])->name('estimate-tolls');
            Route::post('/calculate',       [CalculatorController::class, 'calculate'])->name('calculate');
            Route::post('/eur-rate',        [CalculatorController::class, 'fetchEurRate'])->name('eur-rate');
            Route::post('/save-as-quote',   [CalculatorController::class, 'saveAsQuote'])->name('save-as-quote');
        });

        // Oferty
        Route::prefix('quotes')->name('quotes.')->group(function () {
            Route::get('/',          [QuoteController::class, 'index'])->name('index');
            Route::get('/{quote}',   [QuoteController::class, 'show'])->name('show');
            Route::delete('/{quote}',[QuoteController::class, 'destroy'])->name('destroy');
        });

    });
});

require __DIR__.'/auth.php';
