<?php

namespace App\Providers;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Vehicle;
use App\Observers\AuditObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Index length limit dla starszych MySQL (5.6) i utf8mb4.
        Schema::defaultStringLength(191);

        // Stripe Billable jest na poziomie Organization (per-tenant subskrypcja),
        // nie per-user. Cashier i tak query'uje subscriptions.user_id - ale tutaj
        // ta kolumna trzyma ID organizacji.
        Cashier::useCustomerModel(Organization::class);
        Cashier::calculateTaxes();

        // Logowanie akcji CRUD do audit_log dla kluczowych modeli.
        Quote::observe(AuditObserver::class);
        Invoice::observe(AuditObserver::class);
        Payment::observe(AuditObserver::class);
        Vehicle::observe(AuditObserver::class);
    }
}
