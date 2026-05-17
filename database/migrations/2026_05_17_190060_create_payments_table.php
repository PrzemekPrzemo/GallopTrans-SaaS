<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->decimal('amount_gross', 12, 2);
            $table->decimal('amount_net', 12, 2);
            $table->decimal('vat_amount', 12, 2);
            $table->char('currency', 3)->default('PLN');
            $table->enum('payment_type', ['advance', 'final', 'full', 'other'])->default('full');
            $table->enum('payment_method', ['transfer', 'cash', 'card', 'other'])->default('transfer');
            $table->date('paid_at');
            $table->string('reference', 190)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'paid_at']);
            $table->index('quote_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
