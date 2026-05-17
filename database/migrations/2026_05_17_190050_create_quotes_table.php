<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('number', 40);
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedInteger('sequence');
            $table->foreignId('inquiry_id')->nullable();

            // klient
            $table->string('client_name', 190);
            $table->string('client_email', 190)->nullable();
            $table->string('client_phone', 40)->nullable();
            $table->string('client_company', 190)->nullable();
            $table->string('client_nip', 20)->nullable();
            $table->string('client_address', 255)->nullable();

            // trasa
            $table->string('from_address', 255);
            $table->decimal('from_lat', 10, 7)->nullable();
            $table->decimal('from_lng', 10, 7)->nullable();
            $table->string('to_address', 255);
            $table->decimal('to_lat', 10, 7)->nullable();
            $table->decimal('to_lng', 10, 7)->nullable();
            $table->json('waypoints')->nullable();

            $table->decimal('distance_km', 8, 2)->default(0);
            $table->decimal('return_distance_km', 8, 2)->default(0);
            $table->unsignedInteger('duration_min')->default(0);
            $table->enum('trip_mode', ['one_way', 'round_trip', 'return_home'])->default('round_trip');
            $table->boolean('round_trip')->default(true);

            $table->date('transport_date')->nullable();
            $table->unsignedTinyInteger('horses_count')->default(1);
            $table->foreignId('vehicle_id')->nullable();
            $table->foreignId('trailer_id')->nullable();
            $table->foreignId('driver_id')->nullable();

            // snapshoty parametrów wyceny
            $table->decimal('fuel_consumption', 5, 2);
            $table->decimal('fuel_price', 6, 3);
            $table->decimal('base_rate_per_km', 8, 2);
            $table->decimal('surcharge_percent', 5, 2)->default(0);
            $table->decimal('extra_horse_fee', 8, 2)->default(0);
            $table->decimal('difficult_horse_fee', 8, 2)->default(0);
            $table->decimal('fixed_fees', 10, 2)->default(0);
            $table->decimal('toll_cost', 10, 2)->default(0);
            $table->decimal('min_quote_amount', 10, 2)->default(0);
            $table->unsignedTinyInteger('stay_days')->default(0);
            $table->decimal('stay_24h_cost', 10, 2)->default(0);

            $table->char('currency', 3)->default('PLN');
            $table->decimal('exchange_rate', 10, 4)->default(1.0000);
            $table->decimal('vat_percent', 5, 2)->default(23.00);

            $table->decimal('subtotal_net', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total_gross', 12, 2)->default(0);

            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired', 'cancelled'])->default('draft');
            $table->char('public_token', 40)->unique();
            $table->date('valid_until')->nullable();
            $table->string('pdf_path', 255)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();

            $table->foreignId('created_by')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'year', 'month', 'sequence'], 'q_per_org');
            $table->unique(['organization_id', 'number'], 'q_number');
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
