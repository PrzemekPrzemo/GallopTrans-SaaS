<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->enum('fuel_type', ['diesel', 'petrol', 'lpg'])->default('diesel');
            $table->decimal('price_per_liter', 6, 3);
            $table->char('currency', 3)->default('PLN');
            $table->string('source', 50)->default('manual');
            $table->date('valid_for_date');
            $table->timestamp('fetched_at')->useCurrent();

            $table->unique(['organization_id', 'fuel_type', 'valid_for_date', 'source'], 'fp_unique');
            $table->index(['organization_id', 'valid_for_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_prices');
    }
};
